<?php

use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'Validation Tester',
        'email'    => 'validation-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));
});

it('reports errors against real stock fields, not phantom ones', function () {
    $response = $this->from('/wms/stock/create')->post('/wms/stock/create', []);

    // The bug: BaseModel::rules() demanded stock_id + name, so errors keyed to
    // fields absent from the form and nothing rendered.
    $response->assertSessionHasErrors(['stock_id_product', 'stock_id_lokasi', 'stock_qty', 'stock_type']);
    $response->assertSessionDoesntHaveErrors(['stock_id', 'name']);
    expect(Stock::count())->toBe(0);
});

it('rejects a stock_type outside IN/OUT', function () {
    $gudang = Gudang::create(['gudang_nama' => 'G-'.uniqid()]);
    $lokasi = Lokasi::create(['lokasi_nama' => 'L-'.uniqid(), 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'P-'.uniqid(), 'product_harga' => 10]);

    $this->from('/wms/stock/create')->post('/wms/stock/create', [
        'stock_code'       => 'STK-BAD-TYPE',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 5,
        'stock_type'       => 'SIDEWAYS',
    ])->assertSessionHasErrors('stock_type');

    expect(Stock::count())->toBe(0);
});

it('accepts a fully valid stock payload', function () {
    $gudang = Gudang::create(['gudang_nama' => 'G-'.uniqid()]);
    $lokasi = Lokasi::create(['lokasi_nama' => 'L-'.uniqid(), 'lokasi_id_gudang' => $gudang->gudang_id]);
    $product = Product::create(['product_nama' => 'P-'.uniqid(), 'product_harga' => 10]);

    $this->post('/wms/stock/create', [
        'stock_code'       => 'STK-GOOD',
        'stock_id_product' => $product->product_id,
        'stock_id_lokasi'  => $lokasi->lokasi_id,
        'stock_qty'        => 5,
        'stock_type'       => 'IN',
    ])->assertSessionDoesntHaveErrors();

    expect(Stock::where('stock_code', 'STK-GOOD')->exists())->toBeTrue();
});

it('requires a product name', function () {
    $this->from('/wms/product/create')->post('/wms/product/create', [])
        ->assertSessionHasErrors('product_nama');

    expect(Product::count())->toBe(0);
});

it('requires a gudang name and rejects duplicates', function () {
    $this->from('/wms/gudang/create')->post('/wms/gudang/create', [])
        ->assertSessionHasErrors('gudang_nama');

    Gudang::create(['gudang_nama' => 'Gudang Satu']);

    $this->from('/wms/gudang/create')->post('/wms/gudang/create', ['gudang_nama' => 'Gudang Satu'])
        ->assertSessionHasErrors('gudang_nama');

    expect(Gudang::count())->toBe(1);
});

it('requires lokasi name and a valid gudang', function () {
    $this->from('/wms/lokasi/create')->post('/wms/lokasi/create', [
        'lokasi_nama'      => 'Rak A',
        'lokasi_id_gudang' => 99999,
    ])->assertSessionHasErrors('lokasi_id_gudang');

    expect(Lokasi::count())->toBe(0);
});
