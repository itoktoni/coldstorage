<?php

use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Wms\MasukStatusEnum;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'Forklift Tester',
        'email'    => 'forklift-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));

    $this->gudang = Gudang::create(['gudang_code' => 'GD-'.uniqid(), 'gudang_nama' => 'WH-'.uniqid()]);
    $this->lokasi = Lokasi::create([
        'lokasi_code'        => 'LOC-'.uniqid(),
        'lokasi_nama'        => 'Rak-'.uniqid(),
        'lokasi_code_gudang' => $this->gudang->gudang_code,
        // no lokasi_category → accept any product; no lokasi_max_qty → unlimited capacity
    ]);
    $this->product = Product::create(['product_nama' => 'P-'.uniqid(), 'product_harga' => 10]);

    $this->detail = MasukDetail::create([
        'in_detail_code'       => 'IN-'.now()->format('Ymd').'-'.uniqid(),
        'in_detail_tanggal'    => '2026-01-01',
        'in_detail_status'     => MasukStatusEnum::READY,
        'in_detail_id_product' => $this->product->product_id,
        'in_detail_qty'        => 10,
    ]);

    $this->group = 'PAL-'.now()->format('Ymd').'-'.uniqid();

    MasukRealisasi::create([
        'in_realisasi_masuk_code'   => $this->detail->in_detail_code,
        'in_realisasi_id_product'   => $this->product->product_id,
        'in_realisasi_qty'          => 10,
        'in_realisasi_code_lokasi'  => $this->lokasi->lokasi_code,
        'in_realisasi_barcode'      => 'BC-'.uniqid(),
        'in_realisasi_group'        => $this->group,
    ]);
});

it('stores a Stock IN when a valid pallet is scanned', function () {
    $response = $this->from('/wms/forklift')->post('/wms/forklift', [
        'group_code'  => $this->group,
        'pallet_scan' => $this->group,
        'lokasi_code' => $this->lokasi->lokasi_code,
    ]);

    $response->assertRedirect(route('wms-forklift.index'));
    $response->assertSessionHasNoErrors();

    $stock = Stock::where('stock_reff', $this->group)->first();

    expect($stock)->not->toBeNull()
        ->and($stock->stock_type)->toBe('IN')
        ->and((float) $stock->stock_qty)->toBe(10.0)
        ->and($stock->stock_code_lokasi)->toBe($this->lokasi->lokasi_code);

    // Pallet group marked as located, detail completed
    expect(MasukRealisasi::where('in_realisasi_group', $this->group)->first()->in_realisasi_code_lokasi)
        ->toBe($this->lokasi->lokasi_code);
    expect($this->detail->fresh()->in_detail_status)->toBe(MasukStatusEnum::COMPLETE);
});

it('rejects a scan when pallet_scan does not match group_code', function () {
    $this->from('/wms/forklift')->post('/wms/forklift', [
        'group_code'  => $this->group,
        'pallet_scan' => 'PAL-WRONG',
        'lokasi_code' => $this->lokasi->lokasi_code,
    ])->assertSessionHasErrors(['pallet_scan']);

    expect(Stock::where('stock_reff', $this->group)->exists())->toBeFalse();
});

it('does not create Stock when status is not READY', function () {
    $this->detail->update(['in_detail_status' => MasukStatusEnum::PENDING]);

    $this->from('/wms/forklift')->post('/wms/forklift', [
        'group_code'  => $this->group,
        'pallet_scan' => $this->group,
        'lokasi_code' => $this->lokasi->lokasi_code,
    ])->assertSessionHasErrors(['error']);

    expect(Stock::where('stock_reff', $this->group)->exists())->toBeFalse();
});

