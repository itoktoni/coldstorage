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
        'name' => 'Status Revert Tester',
        'email' => 'revert-'.uniqid().'@test.com',
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

it('reverts SO status to Prepare when allocation is removed from Done prepare', function () {
    $stock = Stock::create([
        'stock_code' => 'TEST-REVERT-'.$this->product->product_id,
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasi->lokasi_code,
        'stock_qty' => 30,
        'stock_type' => Stock::TYPE_STAGING,
    ]);

    // Scan to allocate all 30 → auto-fulfills → Done + Confirmed
    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('scan', $stock->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock '.$stock->stock_code.' dialokasikan.');

    $this->so->refresh();
    expect($this->so->so_status)->toBe(SoStatusEnum::CONFIRMED);

    $prepare = SoPrepare::where('so_prepare_id_so', $this->so->so_id)->first();
    expect($prepare->so_prepare_status)->toBe(SoPrepare::STATUS_DONE);

    // Now remove the allocation
    $detailId = $prepare->details()->first()->so_prepare_detail_id;

    Livewire::test(SoPrepareScan::class, ['soId' => $this->so->so_id])
        ->call('removeAllocation', $detailId)
        ->assertSet('successMsg', 'Alokasi berhasil dihapus.');

    // SO should revert to Prepare, prepare to Pending
    $this->so->refresh();
    expect($this->so->so_status)->toBe(SoStatusEnum::PREPARE);

    $prepare->refresh();
    expect($prepare->so_prepare_status)->toBe(SoPrepare::STATUS_PENDING);

    // Stock should be restored
    expect($stock->fresh()->stock_qty)->toBe(30.0);
});
