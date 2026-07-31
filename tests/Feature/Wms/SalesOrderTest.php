<?php

use App\Models\Customer;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\Stock;
use App\Models\User;

function soActor(): User
{
    $user = User::create([
        'name'     => 'SO Tester',
        'email'    => 'so-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]);

    test()->actingAs($user);

    return $user;
}

function soStock(Product $product, int $qty, ?string $expired = null): Stock
{
    $gudang = \App\Models\Gudang::create(['gudang_nama' => 'G-'.uniqid()]);
    $lokasi = Lokasi::create(['lokasi_nama' => 'L-'.uniqid(), 'lokasi_id_gudang' => $gudang->gudang_id]);

    return Stock::create([
        'stock_code'         => 'STK-'.uniqid(),
        'stock_id_product'   => $product->product_id,
        'stock_id_lokasi'    => $lokasi->lokasi_id,
        'stock_qty'          => $qty,
        'stock_expired_date' => $expired,
        'stock_type'         => 'IN',
    ]);
}

it('auto generates so_code and defaults status to Pending', function () {
    $customer = Customer::create(['customer_nama' => 'Cust A']);

    $so = So::create([
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
    ]);

    expect($so->so_code)->toMatch('/^SO-\d{8}-\d{4}$/');
    expect($so->fresh()->so_status)->toBe(So::STATUS_PENDING);
});

it('cascades delete from so to detail_so', function () {
    $customer = Customer::create(['customer_nama' => 'Cust B']);
    $product = Product::create(['product_nama' => 'SO-Item', 'product_harga' => 500]);

    $so = So::create(['so_tanggal' => '2026-07-31', 'so_id_customer' => $customer->customer_id]);
    SoDetail::create([
        'so_detail_id_so'      => $so->so_id,
        'so_detail_id_product' => $product->product_id,
        'so_detail_qty'        => 2,
        'so_detail_harga'      => 500,
        'so_detail_code'       => 'SOD-CASCADE',
    ]);

    $so->delete();

    expect(SoDetail::where('so_detail_code', 'SOD-CASCADE')->exists())->toBeFalse();
});

it('creates so with price from product and reduces stock', function () {
    soActor();

    $customer = Customer::create(['customer_nama' => 'Cust C']);
    $p1 = Product::create(['product_nama' => 'SO-A', 'product_harga' => 1000]);
    $p2 = Product::create(['product_nama' => 'SO-B', 'product_harga' => 2500]);
    soStock($p1, 10);
    soStock($p2, 10);

    $response = $this->post('/wms/so/create', [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [
            ['so_detail_id_product' => $p1->product_id, 'so_detail_qty' => 3],
            ['so_detail_id_product' => $p2->product_id, 'so_detail_qty' => 4],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();

    $so = So::latest('so_id')->first();
    expect($so->details)->toHaveCount(2);
    expect((float) $so->details->firstWhere('so_detail_id_product', $p1->product_id)->so_detail_harga)->toBe(1000.0);
    expect((float) $so->so_total)->toBe(3 * 1000.0 + 4 * 2500.0);

    expect(Stock::where('stock_id_product', $p1->product_id)->sum('stock_qty'))->toBe(7);
    expect(Stock::where('stock_id_product', $p2->product_id)->sum('stock_qty'))->toBe(6);
});

it('consumes stock oldest expiry first', function () {
    soActor();

    $customer = Customer::create(['customer_nama' => 'Cust FIFO']);
    $product = Product::create(['product_nama' => 'SO-FIFO', 'product_harga' => 100]);
    $old = soStock($product, 5, '2026-01-01');
    $new = soStock($product, 5, '2027-01-01');

    $this->post('/wms/so/create', [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [['so_detail_id_product' => $product->product_id, 'so_detail_qty' => 7]],
    ])->assertSessionDoesntHaveErrors();

    expect($old->fresh()->stock_qty)->toBe(0);
    expect($new->fresh()->stock_qty)->toBe(3);
});

it('rejects so when stock is insufficient', function () {
    soActor();

    $customer = Customer::create(['customer_nama' => 'Cust Short']);
    $product = Product::create(['product_nama' => 'SO-Short', 'product_harga' => 100]);
    soStock($product, 2);

    $this->post('/wms/so/create', [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [['so_detail_id_product' => $product->product_id, 'so_detail_qty' => 5]],
    ]);

    expect(So::count())->toBe(0);
    expect(Stock::where('stock_id_product', $product->product_id)->sum('stock_qty'))->toBe(2);
});

it('adjusts stock by delta on update', function () {
    soActor();

    $customer = Customer::create(['customer_nama' => 'Cust Update']);
    $product = Product::create(['product_nama' => 'SO-Upd', 'product_harga' => 100]);
    soStock($product, 10);

    $this->post('/wms/so/create', [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [['so_detail_id_product' => $product->product_id, 'so_detail_qty' => 4]],
    ])->assertSessionDoesntHaveErrors();

    $so = So::latest('so_id')->first();
    expect(Stock::where('stock_id_product', $product->product_id)->sum('stock_qty'))->toBe(6);

    // qty 4 -> 6 consumes 2 more
    $this->post('/wms/so/update/'.$so->so_id, [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [[
            'so_detail_id'         => $so->details->first()->so_detail_id,
            'so_detail_id_product' => $product->product_id,
            'so_detail_qty'        => 6,
        ]],
    ])->assertSessionDoesntHaveErrors();

    expect(Stock::where('stock_id_product', $product->product_id)->sum('stock_qty'))->toBe(4);

    // qty 6 -> 1 releases 5
    $this->post('/wms/so/update/'.$so->so_id, [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [[
            'so_detail_id'         => $so->fresh()->details->first()->so_detail_id,
            'so_detail_id_product' => $product->product_id,
            'so_detail_qty'        => 1,
        ]],
    ])->assertSessionDoesntHaveErrors();

    expect(Stock::where('stock_id_product', $product->product_id)->sum('stock_qty'))->toBe(9);
});

it('returns stock when so is deleted', function () {
    soActor();

    $customer = Customer::create(['customer_nama' => 'Cust Del']);
    $product = Product::create(['product_nama' => 'SO-Del', 'product_harga' => 100]);
    soStock($product, 10);

    $this->post('/wms/so/create', [
        'so_tanggal'     => '2026-07-31',
        'so_id_customer' => $customer->customer_id,
        'details'        => [['so_detail_id_product' => $product->product_id, 'so_detail_qty' => 4]],
    ])->assertSessionDoesntHaveErrors();

    $so = So::latest('so_id')->first();
    $this->get('/wms/so/delete/'.$so->so_id);

    expect(So::count())->toBe(0);
    expect(Stock::where('stock_id_product', $product->product_id)->sum('stock_qty'))->toBe(10);
});
