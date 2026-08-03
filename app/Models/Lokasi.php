<?php

namespace App\Models;

class Lokasi extends BaseModel
{
    protected $table = 'lokasi';
    protected $primaryKey = 'lokasi_code';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    public static $filterColumns = ['lokasi_code', 'lokasi_nama', 'lokasi_code_gudang', 'lokasi_category'];
    public static $sortColumns   = ['lokasi_code', 'lokasi_category', 'gudang_nama', 'lokasi_nama', 'lokasi_max_qty'];

    protected $fillable = [
        'lokasi_code',
        'lokasi_nama',
        'lokasi_code_gudang',
        'lokasi_max_qty',
        'lokasi_category',
    ];

    protected $casts = [
        'lokasi_max_qty' => 'double',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'lokasi_code_gudang', 'gudang_code');
    }

    public function getGudangNamaAttribute()
    {
        return $this->gudang?->gudang_nama;
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'stock_code_lokasi', 'lokasi_code');
    }

    public static function field_name()
    {
        return 'lokasi_nama';
    }

    /**
     * Get current total stock qty in this lokasi
     */
    public function getCurrentQtyAttribute(): float
    {
        return $this->stock()->where('stock_type', 'IN')->where('stock_qty', '>', 0)->sum('stock_qty');
    }

    /**
     * Check if lokasi can accept a product with given category
     */
    public function canAcceptCategory(?string $productCategory): bool
    {
        // If lokasi has no category restriction, accept anything
        if (empty($this->lokasi_category)) {
            return true;
        }

        // If product has no category, cannot place in category-restricted lokasi
        if (empty($productCategory)) {
            return false;
        }

        // Categories must match (case-insensitive)
        return strtolower($this->lokasi_category) === strtolower($productCategory);
    }

    /**
     * Check if lokasi has capacity for additional qty
     */
    public function hasCapacity(float $additionalQty = 0): bool
    {
        // If no max_qty set, unlimited capacity
        if (is_null($this->lokasi_max_qty)) {
            return true;
        }

        return ($this->current_qty + $additionalQty) <= $this->lokasi_max_qty;
    }

    public function rules(): array
    {
        return [
            'lokasi_code'        => ['required', 'string', 'max:50'],
            'lokasi_nama'        => ['required', 'string', 'max:100'],
            'lokasi_code_gudang' => ['required', 'exists:gudang,gudang_code'],
            'lokasi_max_qty'     => ['nullable', 'numeric', 'min:0'],
            'lokasi_category'    => ['nullable', 'string', 'max:50'],
        ];
    }
}
