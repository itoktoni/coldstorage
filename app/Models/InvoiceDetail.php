<?php

namespace App\Models;

class InvoiceDetail extends BaseModel
{
    protected $table = 'invoice_detail';

    protected $primaryKey = 'invoice_detail_id';

    public $timestamps = true;

    protected $fillable = [
        'invoice_detail_id_invoice',
        'invoice_detail_id_product',
        'invoice_detail_qty',
        'invoice_detail_harga',
        'invoice_detail_subtotal',
    ];

    protected $casts = [
        'invoice_detail_qty' => 'decimal:3',
        'invoice_detail_harga' => 'decimal:2',
        'invoice_detail_subtotal' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_detail_id_invoice', 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'invoice_detail_id_product', 'product_id');
    }

    public function getHargaAttribute(): float
    {
        $harga = (float) $this->invoice_detail_harga;

        return $harga > 0 ? $harga : (float) ($this->product->product_harga ?? 0);
    }
}
