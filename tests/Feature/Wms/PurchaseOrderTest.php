<?php

use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;

it('defaults po_status to Pending', function () {
    $po = Po::create([
        'po_tanggal'    => '2026-07-31',
        'po_code'       => 'PO-TEST-001',
        'po_supplier'   => 'Supplier A',
        'po_keterangan' => null,
    ]);

    expect($po->fresh()->po_status)->toBe(Po::STATUS_PENDING);
});

it('persists po with details and product relation', function () {
    $product = Product::create(['product_nama' => 'PO-Item-1', 'product_harga' => 100]);

    $po = Po::create([
        'po_tanggal'  => '2026-07-31',
        'po_code'     => 'PO-TEST-002',
        'po_supplier' => 'Supplier B',
        'po_status'   => Po::STATUS_ORDERED,
    ]);

    $detail = PoDetail::create([
        'po_detail_id_po'      => $po->po_id,
        'po_detail_id_product' => $product->product_id,
        'po_detail_qty'        => 10,
        'po_detail_code'       => 'POD-0001',
    ]);

    expect($po->details)->toHaveCount(1);
    expect($detail->po)->toBeInstanceOf(Po::class);
    expect($detail->product)->toBeInstanceOf(Product::class);
    expect($detail->product->product_nama)->toBe('PO-Item-1');
});

it('cascades delete from po to detail_po', function () {
    $product = Product::create(['product_nama' => 'PO-Item-2', 'product_harga' => 50]);

    $po = Po::create([
        'po_tanggal'  => '2026-07-31',
        'po_code'     => 'PO-TEST-003',
        'po_supplier' => 'Supplier C',
    ]);

    PoDetail::create([
        'po_detail_id_po'      => $po->po_id,
        'po_detail_id_product' => $product->product_id,
        'po_detail_qty'        => 5,
        'po_detail_code'       => 'POD-0002',
    ]);

    $po->delete();

    expect(PoDetail::query()->where('po_detail_code', 'POD-0002')->exists())->toBeFalse();
});
