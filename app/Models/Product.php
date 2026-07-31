<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = true;

    public static $filterColumns = ['product_nama'];
    public static $sortColumns   = ['product_id', 'product_nama'];

    protected $fillable = [
        'product_nama',
        'product_harga',
    ];

    protected $casts = [
        'product_harga' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->hasMany(Stock::class, 'stock_id_product', 'product_id');
    }

    public function getQtyAttribute()
    {
        return $this->relationLoaded('stock') ? $this->stock->sum('stock_qty') : $this->stock()->sum('stock_qty');
    }

    public function getTanggalAttribute()
    {
        return $this->relationLoaded('stock')
            ? optional($this->stock->sortByDesc('stock_expired_date')->first())->stock_expired_date
            : $this->stock()->max('stock_expired_date');
    }
}
