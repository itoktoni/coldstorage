<?php

use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Split;
use App\Models\Stock;

it('links a split row to product and stock', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-H']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-08', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-7', 'product_harga' => 100]);
    $stock = Stock::create([
        'stock_code'       => 'STK-SPLIT-001',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 200,
        'stock_type'       => 'IN',
    ]);

    $split = Split::create([
        'split_id_product' => $product->product_id,
        'split_id_stock'   => $stock->stock_id,
        'split_qty_new'    => 150,
        'split_qty_old'    => 200,
        'split_qty_waste'  => 0,
        'split_tanggal'    => '2026-01-01',
    ]);

    expect($split->product)->toBeInstanceOf(Product::class);
    expect($split->stock)->toBeInstanceOf(Stock::class);
    expect((float) $split->split_qty_new)->toBe(150.0);
});