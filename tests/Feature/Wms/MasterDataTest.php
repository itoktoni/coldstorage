<?php

use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Schema;

it('creates the wms tables', function () {
    expect(Schema::hasTable('gudang'))->toBeTrue();
    expect(Schema::hasTable('lokasi'))->toBeTrue();
    expect(Schema::hasTable('product'))->toBeTrue();
    expect(Schema::hasTable('stock'))->toBeTrue();
    expect(Schema::hasTable('masuk_detail'))->toBeTrue();
    expect(Schema::hasTable('masuk_realisasi'))->toBeTrue();
    expect(Schema::hasTable('keluar'))->toBeTrue();
    expect(Schema::hasTable('keluar_detail'))->toBeTrue();
    expect(Schema::hasTable('keluar_realisasi'))->toBeTrue();
    expect(Schema::hasTable('split'))->toBeTrue();
});

it('relates gudang to lokasi via gudang_id', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-A']);
    $lokasi = Lokasi::create([
        'lokasi_nama'      => 'Rak-01',
        'lokasi_id_gudang' => $gudang->gudang_id,
    ]);

    expect($lokasi->gudang)->toBeInstanceOf(Gudang::class);
    expect($lokasi->gudang->gudang_id)->toBe($gudang->gudang_id);
    expect($gudang->lokasi)->toHaveCount(1);
    expect($gudang->lokasi->first()->lokasi_id)->toBe($lokasi->lokasi_id);
});

it('relates lokasi to stock and stock to product', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-B']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-02', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-1', 'product_harga' => 1000]);

    $stock = Stock::create([
        'stock_code'       => 'STK-001',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 10,
        'stock_type'       => 'IN',
    ]);

    expect($stock->product)->toBeInstanceOf(Product::class);
    expect($stock->lokasi)->toBeInstanceOf(Lokasi::class);
    expect($lokasi->stock)->toHaveCount(1);
});

it('scopes available stock to stock_type IN and positive qty', function () {
    $gudang = Gudang::create(['gudang_nama' => 'WH-C']);
    $lokasi = Lokasi::create(['lokasi_nama' => 'Rak-03', 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'Item-2', 'product_harga' => 2000]);

    Stock::create(['stock_code' => 'S1', 'stock_id_product' => $product->product_id, 'stock_id_lokasi' => $lokasi->lokasi_id, 'stock_qty' => 5, 'stock_type' => 'IN']);
    Stock::create(['stock_code' => 'S2', 'stock_id_product' => $product->product_id, 'stock_id_lokasi' => $lokasi->lokasi_id, 'stock_qty' => 0, 'stock_type' => 'IN']);
    Stock::create(['stock_code' => 'S3', 'stock_id_product' => $product->product_id, 'stock_id_lokasi' => $lokasi->lokasi_id, 'stock_qty' => 3, 'stock_type' => 'OUT']);

    expect(Stock::available()->count())->toBe(1);
});