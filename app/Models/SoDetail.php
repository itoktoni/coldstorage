<?php

namespace App\Models;

class SoDetail extends BaseModel
{
    protected $table = 'detail_so';

    protected $primaryKey = 'so_detail_id';

    public $timestamps = true;

    public static $filterColumns = ['so_detail_code', 'so_detail_id_product', 'so_detail_id_so'];

    public static $sortColumns = ['so_detail_code', 'so_detail_qty', 'so_detail_harga'];

    protected $fillable = [
        'so_detail_id_so',
        'so_detail_id_product',
        'so_detail_qty',
        'so_detail_harga',
        'so_detail_code',
    ];

    protected $casts = [
        'so_detail_qty' => 'integer',
        'so_detail_harga' => 'decimal:2',
    ];

    public function so()
    {
        return $this->belongsTo(So::class, 'so_detail_id_so', 'so_id');
    }

    public function getHargaAttribute(): float
    {
        $harga = (float) $this->so_detail_harga;

        return $harga > 0 ? $harga : (float) ($this->product->product_harga ?? 0);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'so_detail_id_product', 'product_id');
    }

    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_so_detail');
    }
}
