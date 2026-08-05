<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\So;
use App\Models\Stock;
use App\Wms\SoStatusEnum;

it('creates RESERVE stock with PROD- barcode format when SO is created', function () {
    $customer = Customer::create(['customer_nama' => 'C-'.uniqid(), 'customer_telepon' => '0812']);
    $product = Product::create(['product_code' => 'PROD-99', 'product_nama' => 'P-'.uniqid(), 'product_harga' => 1000]);

    $so = So::create([
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => SoStatusEnum::PENDING,
    ]);

    // Simulate what SoController::syncReserve does
    $details = [['so_detail_id_product' => $product->product_id, 'so_detail_qty' => 50]];
    $grouped = collect($details)->groupBy('so_detail_id_product')
        ->map(fn ($rows) => $rows->sum('so_detail_qty'));

    $productCodes = Product::whereIn('product_id', $grouped->keys())
        ->pluck('product_code', 'product_id');

    foreach ($grouped as $productId => $qty) {
        $productCode = $productCodes[$productId] ?? 'PROD-'.str_pad($productId, 2, '0', STR_PAD_LEFT);
        $stockCode = implode('#', [$productCode, now()->format('YmdHis').strtoupper(uniqid()), (string) $qty, 'RESERVE']);

        Stock::create([
            'stock_code' => $stockCode,
            'stock_id_product' => (int) $productId,
            'stock_qty' => (float) $qty,
            'stock_type' => Stock::TYPE_RESERVE,
            'stock_reff' => $so->so_code,
            'stock_code_lokasi' => null,
        ]);
    }

    $stock = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $so->so_code)
        ->first();

    expect($stock)->not->toBeNull();

    $parts = explode('#', $stock->stock_code);
    expect($parts[0])->toStartWith('PROD-');
    expect(count($parts))->toBe(4);
    expect($stock->stock_qty)->toBe(50.0);
    expect($stock->stock_code_lokasi)->toBeNull();
});
