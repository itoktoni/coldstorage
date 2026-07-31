<?php

namespace App\Models;

class PoDetail extends BaseModel
{
    protected $table = 'detail_po';
    protected $primaryKey = 'po_detail_id';
    public $timestamps = true;

    public static $filterColumns = ['po_detail_code', 'po_detail_id_product', 'po_detail_id_po'];
    public static $sortColumns   = ['po_detail_code', 'po_detail_qty'];

    protected $fillable = [
        'po_detail_id_po',
        'po_detail_id_product',
        'po_detail_qty',
        'po_detail_code',
    ];

    public function po()
    {
        return $this->belongsTo(Po::class, 'po_detail_id_po', 'po_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'po_detail_id_product', 'product_id');
    }
}
