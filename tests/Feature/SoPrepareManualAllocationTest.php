<?php

use App\Livewire\SoPrepareScan;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\Stock;
use App\Models\User;
use App\Wms\SoStatusEnum;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Manual Alloc Tester',
        'email' => 'alloc-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]));

    $uniqid = uniqid();
    $this->gudang = Gudang::create(['gudang_code' => 'GD-'.$uniqid, 'gudang_nama' => 'G '.$uniqid]);
    $this->lokasi = Lokasi::create([
        'lokasi_code' => 'LOC-'.$uniqid,
        'lokasi_nama' => 'L '.$uniqid,
        'lokasi_code_gudang' => $this->gudang->gudang_code,
        'lokasi_category' => 'staging',
        'lokasi_max_qty' => 1000,
    ]);
    $this->product = Product::create(['product_nama' => 'P '.$uniqid, 'product_harga' => 1000]);
    $this->customer = Customer::create(['customer_nama' => 'C '.$uniqid, 'customer_telepon' => '0812']);

    $this->so = So::create([
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status' => SoStatusEnum::PREPARE,
    ]);
    SoDetail::create([
        'so_detail_id_so' => $this->so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 30,
        'so_detail_harga' => 1000,
        'so_detail_code' => $this->so->so_code.'-001',
    ]);
});

it('manual allocation defaults to min(so_need, stock_remaining)', function () {
    $stock = Stock::create([
        'stock_code' => 'TEST-MANUAL-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 50,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    $component = Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id]);

    // Stock has 50, SO needs 30 → default should be 30 (min of 50, 30)
    expect($component->get('assignQtys.'.$stock->stock_id))->toBe('30');

    // Manual allocate 10 (not the full 30)
    $component->call('assignStock', $stock->stock_id, 10.0);

    // Stock remaining = 50-10 = 40, assigned for stock = 10, so qty_remaining = 30
    // SO remaining = 30-10 = 20
    $stockRows = $component->get('stockRows');
    $row = collect($stockRows)->firstWhere('stock_id', $stock->stock_id);
    expect($row['so_need_remaining'])->toBe(20.0);

    // Default should now be min(20, stock_remaining) = 20
    $component2 = Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id]);
    expect($component2->get('assignQtys.'.$stock->stock_id))->toBe('20');
});

it('manual allocation caps at stock_remaining when stock < so_need', function () {
    $stock = Stock::create([
        'stock_code' => 'TEST-CAP-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 10,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    $component = Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id]);

    // Stock has 10, SO needs 30 → default should be 10 (min of 10, 30)
    expect($component->get('assignQtys.'.$stock->stock_id))->toBe('10');
});

it('hides allocate button when so_need is fully satisfied', function () {
    $stock = Stock::create([
        'stock_code' => 'TEST-HIDE-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 30,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSuccessful();

    $component = Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id]);

    // All 30 allocated → so_need for product should be 0
    // Stock qty=0, so it no longer appears in stockRows (no allocate button)
    $prepare = SoPrepare::where('so_prepare_id_so', $this->so->so_id)->first();
    $totalAllocated = (float) $prepare->details()
        ->where('so_prepare_detail_id_product', $this->product->product_id)
        ->sum('so_prepare_detail_qty');
    expect($totalAllocated)->toBe(30.0);
    expect((float) $this->so->details->first()->so_detail_qty - $totalAllocated)->toBe(0.0);
});
