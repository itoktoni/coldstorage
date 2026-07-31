<?php

namespace App\Models;

class Split extends BaseModel
{
    protected $table = 'split';
    protected $primaryKey = 'split_id';
    public $timestamps = true;

    public static $filterColumns = ['split_id_product', 'split_id_stock', 'split_tanggal'];
    public static $sortColumns   = ['split_tanggal', 'split_id'];

    protected $fillable = [
        'split_id_product',
        'split_id_stock',
        'split_id_reff',
        'split_qty_new',
        'split_qty_old',
        'split_qty_waste',
        'split_tanggal',
        'split_created_by',
        'split_created_at',
    ];

    protected $casts = [
        'split_qty_new'    => 'double',
        'split_qty_old'    => 'double',
        'split_qty_waste'  => 'double',
        'split_tanggal'    => 'date',
        'split_created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'split_id_product', 'product_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'split_id_stock', 'stock_id');
    }
}
