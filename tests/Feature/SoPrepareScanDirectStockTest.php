<?php

use App\Livewire\SoPrepareScan;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\User;
use App\Wms\SoStatusEnum;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'SO Scan Tester',
        'email' => 'so-scan-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]));

    $uniqid = uniqid();
    $this->gudang = Gudang::create(['gudang_code' => 'GD-'.$uniqid, 'gudang_nama' => 'Gudang '.$uniqid]);
    $this->lokasi = Lokasi::create([
        'lokasi_code' => 'LOC-'.$uniqid,
        'lokasi_nama' => 'Lokasi '.$uniqid,
        'lokasi_code_gudang' => $this->gudang->gudang_code,
        'lokasi_category' => 'staging',
        'lokasi_max_qty' => 1000,
    ]);
    $this->product = Product::create(['product_nama' => 'Produk '.$uniqid, 'product_harga' => 5000]);
    $this->customer = Customer::create(['customer_nama' => 'Cust '.$uniqid, 'customer_telepon' => '0812']);

    $this->so = So::create([
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status' => SoStatusEnum::PREPARE,
    ]);
    SoDetail::create([
        'so_detail_id_so' => $this->so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 50,
        'so_detail_harga' => 5000,
        'so_detail_code' => $this->so->so_code.'-001',
    ]);
});

it('scans staging stock without keluar_detail and cuts stock directly', function () {
    $stock = Stock::create([
        'stock_code' => 'STK-TEST-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 30,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock '.$stock->stock_code.' dialokasikan.');

    // Stock was cut directly
    expect($stock->fresh()->stock_qty)->toBe(0.0);

    // Allocation tracked with stock_id and nullable realisasi
    $detail = SoPrepareDetail::where('so_prepare_detail_id_stock', $stock->stock_id)->first();
    expect($detail)->not->toBeNull()
        ->and($detail->so_prepare_detail_id_realisasi)->toBeNull()
        ->and((float) $detail->so_prepare_detail_qty)->toBe(30.0);
});

it('scans IN stock without keluar_detail and cuts stock directly', function () {
    $stock = Stock::create([
        'stock_code' => 'STK-TESTIN-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 40,
        'stock_type' => Stock::TYPE_IN,
    ]);

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSuccessful();

    // SO needs 50, stock has 40 → all 40 is allocated and cut
    expect($stock->fresh()->stock_qty)->toBe(0.0);

    $detail = SoPrepareDetail::where('so_prepare_detail_id_stock', $stock->stock_id)->first();
    expect($detail)->not->toBeNull()
        ->and($detail->so_prepare_detail_id_realisasi)->toBeNull()
        ->and((float) $detail->so_prepare_detail_qty)->toBe(40.0);
});

it('scans and creates keluar_realisasi when a keluar_detail exists', function () {
    // Create the keluar + detail (legacy prepare flow)
    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status' => 'Pending',
        'out_reff' => 'Prepare SO',
        'out_qty' => 50,
    ]);
    KeluarDetail::create([
        'out_detail_code_keluar' => $keluar->out_code,
        'out_detail_id_product' => $this->product->product_id,
        'out_detail_id_so_detail' => $this->so->details()->first()->so_detail_id,
        'out_detail_code' => $keluar->out_code.'-001',
        'out_detail_qty' => 50,
        'out_detail_reff' => $this->so->details()->first()->so_detail_code,
    ]);

    $stock = Stock::create([
        'stock_code' => 'STK-TESTKD-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 30,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock '.$stock->stock_code.' dialokasikan.');

    expect($stock->fresh()->stock_qty)->toBe(0.0);

    $detail = SoPrepareDetail::where('so_prepare_detail_id_stock', $stock->stock_id)->first();
    expect($detail)->not->toBeNull()
        ->and($detail->so_prepare_detail_id_realisasi)->not->toBeNull();
});

it('marks SO confirmed when all lines are fulfilled by scanning', function () {
    $stock = Stock::create([
        'stock_code' => 'STK-TESTFUL-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 50,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock '.$stock->stock_code.' dialokasikan.');

    $prepare = SoPrepare::where('so_prepare_id_so', $this->so->so_id)->first();
    expect($prepare->so_prepare_status)->toBe(SoPrepare::STATUS_DONE)
        ->and($this->so->fresh()->so_status)->toBe(SoStatusEnum::CONFIRMED);
});
