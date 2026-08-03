<?php

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Models\User;
use App\Wms\SoStatusEnum;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'Assign Tester',
        'email'    => 'assign-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));

    $this->gudang = Gudang::create(['gudang_code' => 'GDG-A', 'gudang_nama' => 'Gudang A']);
    $this->lokasi = Lokasi::create([
        'lokasi_code'         => 'B1',
        'lokasi_nama'         => 'Rak B1',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);
    $this->lokasi2 = Lokasi::create([
        'lokasi_code'         => 'B2',
        'lokasi_nama'         => 'Rak B2',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);

    $this->product = Product::create(['product_nama' => 'Product A', 'product_harga' => 100]);
    $this->customer = Customer::create(['customer_nama' => 'Cust-'.uniqid()]);
});

it('assigns stock to keluar detail', function () {
    $stock = Stock::create([
        'stock_id_product'  => $this->product->product_id,
        'stock_qty'         => 50,
        'stock_type'        => Stock::TYPE_IN,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
    ]);

    $so = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $detail = SoDetail::create([
        'so_detail_id_so'       => $so->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 20,
        'so_detail_code'        => $so->so_code.'-001',
    ]);

    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status'  => 'Pending',
        'out_reff'    => 'Prepare SO',
        'out_qty'     => 20,
    ]);
    $keluarDetail = KeluarDetail::create([
        'out_detail_code_keluar'  => $keluar->out_code,
        'out_detail_id_product'   => $this->product->product_id,
        'out_detail_id_so_detail' => $detail->so_detail_id,
        'out_detail_code'         => $keluar->out_code.'-001',
        'out_detail_qty'          => 20,
    ]);

    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $keluarDetail->out_detail_id,
                'stock_id'         => $stock->stock_id,
                'qty'              => 20,
                'so_detail_id'     => $detail->so_detail_id,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('stock_assignment', [
        'stock_assignment_id_keluar'         => $keluar->out_code,
        'stock_assignment_id_stock'          => $stock->stock_id,
        'stock_assignment_qty'               => 20,
        'stock_assignment_status'            => 'Pending',
    ]);
});

it('rejects assign when qty exceeds stock remaining', function () {
    $stock = Stock::create([
        'stock_id_product'  => $this->product->product_id,
        'stock_qty'         => 10,
        'stock_type'        => Stock::TYPE_IN,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
    ]);

    $so = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $detail = SoDetail::create([
        'so_detail_id_so'       => $so->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 20,
        'so_detail_code'        => $so->so_code.'-001',
    ]);

    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status'  => 'Pending',
        'out_reff'    => 'Prepare SO',
        'out_qty'     => 20,
    ]);
    $keluarDetail = KeluarDetail::create([
        'out_detail_code_keluar'  => $keluar->out_code,
        'out_detail_id_product'   => $this->product->product_id,
        'out_detail_id_so_detail' => $detail->so_detail_id,
        'out_detail_code'         => $keluar->out_code.'-001',
        'out_detail_qty'          => 20,
    ]);

    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $keluarDetail->out_detail_id,
                'stock_id'         => $stock->stock_id,
                'qty'              => 15,
                'so_detail_id'     => $detail->so_detail_id,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('stock_assignment', 0);
});

it('allows splitting one stock across multiple SOs', function () {
    $stock = Stock::create([
        'stock_id_product'  => $this->product->product_id,
        'stock_qty'         => 30,
        'stock_type'        => Stock::TYPE_IN,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
    ]);

    $so1 = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $d1 = SoDetail::create([
        'so_detail_id_so'       => $so1->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 20,
        'so_detail_code'        => $so1->so_code.'-001',
    ]);
    $k1 = Keluar::create(['out_tanggal' => now()->toDateString(), 'out_status' => 'Pending', 'out_reff' => 'Prepare SO', 'out_qty' => 20]);
    $kd1 = KeluarDetail::create([
        'out_detail_code_keluar' => $k1->out_code, 'out_detail_id_product' => $this->product->product_id,
        'out_detail_id_so_detail' => $d1->so_detail_id, 'out_detail_code' => $k1->out_code.'-001', 'out_detail_qty' => 20,
    ]);

    $so2 = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $d2 = SoDetail::create([
        'so_detail_id_so'       => $so2->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 15,
        'so_detail_code'        => $so2->so_code.'-001',
    ]);
    $k2 = Keluar::create(['out_tanggal' => now()->toDateString(), 'out_status' => 'Pending', 'out_reff' => 'Prepare SO', 'out_qty' => 15]);
    $kd2 = KeluarDetail::create([
        'out_detail_code_keluar' => $k2->out_code, 'out_detail_id_product' => $this->product->product_id,
        'out_detail_id_so_detail' => $d2->so_detail_id, 'out_detail_code' => $k2->out_code.'-001', 'out_detail_qty' => 15,
    ]);

    // Assign 20 to SO-001
    $this->post(route('wms-so-prepare.assignStore', ['soId' => $so1->so_id]), [
        'assignments' => [
            ['keluar_detail_id' => $kd1->out_detail_id, 'stock_id' => $stock->stock_id, 'qty' => 20, 'so_detail_id' => $d1->so_detail_id],
        ],
    ]);

    // Assign 10 to SO-002 (remaining = 30 - 20 = 10)
    $this->post(route('wms-so-prepare.assignStore', ['soId' => $so2->so_id]), [
        'assignments' => [
            ['keluar_detail_id' => $kd2->out_detail_id, 'stock_id' => $stock->stock_id, 'qty' => 10, 'so_detail_id' => $d2->so_detail_id],
        ],
    ]);

    $this->assertDatabaseHas('stock_assignment', [
        'stock_assignment_id_stock' => $stock->stock_id,
        'stock_assignment_qty' => 20,
    ]);
    $this->assertDatabaseHas('stock_assignment', [
        'stock_assignment_id_stock' => $stock->stock_id,
        'stock_assignment_qty' => 10,
    ]);
});

it('shows assign page with available stock', function () {
    $stock = Stock::create([
        'stock_id_product'  => $this->product->product_id,
        'stock_qty'         => 50,
        'stock_type'        => Stock::TYPE_IN,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
    ]);

    $so = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $detail = SoDetail::create([
        'so_detail_id_so'       => $so->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 20,
        'so_detail_code'        => $so->so_code.'-001',
    ]);

    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status'  => 'Pending',
        'out_reff'    => 'Prepare SO',
        'out_qty'     => 20,
    ]);
    KeluarDetail::create([
        'out_detail_code_keluar'  => $keluar->out_code,
        'out_detail_id_product'   => $this->product->product_id,
        'out_detail_id_so_detail' => $detail->so_detail_id,
        'out_detail_code'         => $keluar->out_code.'-001',
        'out_detail_qty'          => 20,
    ]);

    $response = $this->get(route('wms-so-prepare.assign', ['soId' => $so->so_id]));
    $response->assertOk();
    $response->assertSee('Assign Stock');
    $response->assertSee($stock->stock_code);
});
