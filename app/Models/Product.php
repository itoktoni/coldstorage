<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = true;

    public static $filterColumns = ['product_nama', 'product_category'];
    public static $sortColumns   = ['product_code', 'product_category', 'product_nama'];

    protected $fillable = [
        'product_code',
        'product_nama',
        'product_harga',
        'product_category',
    ];

    protected $casts = [
        'product_harga' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->hasMany(Stock::class, 'stock_id_product', 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'product_category', 'slug');
    }

    public static function field_name()
    {
        return 'product_nama';
    }

    protected static function booted(): void
    {
        static::created(function (self $product) {
            if (empty($product->product_code)) {
                $product->update(['product_code' => 'P' . str_pad($product->product_id, 10, '0', STR_PAD_LEFT)]);
            }
        });
    }

    public function rules(): array
    {
        return [
            'product_code'        => ['nullable', 'string', 'max:11', 'unique:product,product_code'],
            'product_nama'        => ['required', 'string', 'max:200'],
            'product_harga'       => ['required', 'numeric', 'min:0'],
            'product_category' => ['nullable', 'string', 'max:50', 'exists:categories,slug'],
        ];
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
