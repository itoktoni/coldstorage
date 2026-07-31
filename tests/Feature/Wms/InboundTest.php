<?php

use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;

it('persists masuk_realisasi with string FK to masuk_detail', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-D']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-04', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-3', 'product_harga' => 500]);

    $masuk = MasukDetail::create([
        'in_detail_code'      => 'IN-20260101-0001',
        'in_detail_tanggal'   => '2026-01-01',
        'in_detail_status'    => MasukDetail::STATUS_PENDING,
        'in_detail_id_product' => $product->product_id,
        'in_detail_qty'       => 100,
    ]);

    $realisasi = MasukRealisasi::create([
        'in_realisasi_masuk_code' => $masuk->in_detail_code,
        'in_realisasi_code'       => 'RLS-0001',
        'in_realisasi_id_product' => $product->product_id,
        'in_realisasi_qty'        => 100,
        'in_realisasi_group'      => 1,
    ]);

    expect($realisasi->masukDetail)->toBeInstanceOf(MasukDetail::class);
    expect($realisasi->masukDetail->in_detail_code)->toBe($masuk->in_detail_code);
    expect($masuk->realisasi)->toHaveCount(1);
});

it('cascades from masuk_detail to masuk_realisasi', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-E']);
    Lokasi::create(['lokasi_nama' => 'Rak-05', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-4', 'product_harga' => 750]);

    $masuk = MasukDetail::create([
        'in_detail_code'      => 'IN-20260101-0002',
        'in_detail_tanggal'   => '2026-01-01',
        'in_detail_id_product' => $product->product_id,
        'in_detail_qty'       => 50,
    ]);
    MasukRealisasi::create([
        'in_realisasi_masuk_code' => $masuk->in_detail_code,
        'in_realisasi_code'       => 'RLS-0002',
        'in_realisasi_id_product' => $product->product_id,
        'in_realisasi_qty'        => 50,
    ]);

    $masuk->delete();

    expect(MasukRealisasi::query()->where('in_realisasi_masuk_code', 'IN-20260101-0002')->exists())->toBeFalse();
});