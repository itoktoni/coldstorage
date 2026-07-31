<?php

use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Stock;

it('persists keluar_realisasi with string FK to keluar', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-F']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-06', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-5', 'product_harga' => 300]);
    $stock = Stock::create([
        'stock_code'       => 'STK-OUT-001',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 50,
        'stock_type'       => 'IN',
    ]);

    $keluar = Keluar::create([
        'out_code'    => 'OUT-20260101-0001',
        'out_tanggal' => '2026-01-01',
        'out_status'  => Keluar::STATUS_PENDING,
    ]);

    $detail = KeluarDetail::create([
        'out_detail_code_keluar' => $keluar->out_code,
        'out_detail_id_product'  => $product->product_id,
        'out_detail_code'        => 'OUTD-0001',
        'out_detail_qty'         => 10,
    ]);

    $realisasi = KeluarRealisasi::create([
        'out_realisasi_id_detail' => $detail->out_detail_id,
        'out_realisasi_code'      => 'OUTR-0001',
        'out_realisasi_id_stock'  => $stock->stock_id,
    ]);

    expect($detail->keluar)->toBeInstanceOf(Keluar::class);
    expect($realisasi->stock)->toBeInstanceOf(Stock::class);
    expect($realisasi->detail)->toBeInstanceOf(KeluarDetail::class);
    expect($keluar->details)->toHaveCount(1);
});

it('cascades from keluar to keluar_detail and keluar_realisasi', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-G']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-07', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-6', 'product_harga' => 900]);
    $stock = Stock::create([
        'stock_code'       => 'STK-OUT-002',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 25,
        'stock_type'       => 'IN',
    ]);

    $keluar = Keluar::create([
        'out_code'    => 'OUT-20260101-0002',
        'out_tanggal' => '2026-01-01',
    ]);
    $detail = KeluarDetail::create([
        'out_detail_code_keluar' => $keluar->out_code,
        'out_detail_id_product'  => $product->product_id,
        'out_detail_code'        => 'OUTD-0002',
        'out_detail_qty'         => 5,
    ]);
    KeluarRealisasi::create([
        'out_realisasi_id_detail' => $detail->out_detail_id,
        'out_realisasi_code'      => 'OUTR-0002',
        'out_realisasi_id_stock'  => $stock->stock_id,
    ]);

    $keluar->delete();

    expect(KeluarDetail::query()->where('out_detail_code_keluar', 'OUT-20260101-0002')->exists())->toBeFalse();
    expect(KeluarRealisasi::query()->where('out_realisasi_code', 'OUTR-0002')->exists())->toBeFalse();
});