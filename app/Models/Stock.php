<?php

namespace App\Models;

use Illuminate\Validation\Rule;

class Stock extends BaseModel
{
    protected $table = 'stock';
    protected $primaryKey = 'stock_id';
    public $timestamps = true;

    public static $filterColumns = ['stock_code', 'stock_id_product', 'stock_id_lokasi', 'stock_type'];
    public static $sortColumns   = ['product_nama', 'lokasi_nama', 'stock_qty', 'stock_expired_date'];

    protected $fillable = [
        'stock_code',
        'stock_id_product',
        'stock_id_lokasi',
        'stock_qty',
        'stock_expired_date',
        'stock_reff',
        'stock_type',
    ];

    protected $casts = [
        'stock_expired_date' => 'date',
        'stock_qty'          => 'double',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $stock) {
            if (empty($stock->stock_code)) {
                $stock->stock_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'STK-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('stock_code', $code)->exists());

        return $code;
    }

    public function rules(): array
    {
        $id = $this->exists ? $this->stock_id : null;

        return [
            'stock_code'         => ['nullable', 'string', 'max:100', Rule::unique('stock', 'stock_code')->ignore($id, 'stock_id')],
            'stock_id_product'   => ['required', 'exists:product,product_id'],
            'stock_id_lokasi'    => ['required', 'exists:lokasi,lokasi_id'],
            'stock_qty'          => ['required', 'numeric', 'min:0'],
            'stock_expired_date' => ['nullable', 'date'],
            'stock_reff'         => ['nullable', 'string', 'max:100'],
            'stock_type'         => ['required', 'in:IN,OUT'],
        ];
    }

    public static function typeOptions(): array
    {
        return ['IN' => 'IN', 'OUT' => 'OUT'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'stock_id_product', 'product_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'stock_id_lokasi', 'lokasi_id');
    }

    public function keluarRealisasi()
    {
        return $this->hasMany(KeluarRealisasi::class, 'out_realisasi_id_stock', 'stock_id');
    }

    public function splits()
    {
        return $this->hasMany(Split::class, 'split_id_stock', 'stock_id');
    }

    /** Available (IN) stock for inventory queries */
    public function scopeAvailable($query)
    {
        return $query->where('stock_type', 'IN')->where('stock_qty', '>', 0);
    }

    public function getProductNamaAttribute()
    {
        return $this->relationLoaded('product') ? ($this->product->product_nama ?? '-') : ($this->product()->value('product_nama') ?? '-');
    }

    public function getLokasiNamaAttribute()
    {
        return $this->relationLoaded('lokasi') ? ($this->lokasi->lokasi_nama ?? '-') : ($this->lokasi()->value('lokasi_nama') ?? '-');
    }

    /**
     * Consume $qty of a product from available stock, oldest expiry first.
     *
     * @throws \RuntimeException when available stock is insufficient
     */
    public static function consume(int $productId, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        // ponytail: NULL expiry sorts first on MySQL/SQLite — good enough for FIFO here.
        $rows = self::query()->available()
            ->where('stock_id_product', $productId)
            ->orderBy('stock_expired_date')
            ->lockForUpdate()
            ->get();

        if ($rows->sum('stock_qty') < $qty) {
            throw new \RuntimeException('Stock tidak cukup untuk product #'.$productId.' (butuh '.$qty.', tersedia '.$rows->sum('stock_qty').').');
        }

        $left = $qty;
        foreach ($rows as $row) {
            $take = min($left, (float) $row->stock_qty);
            $row->decrement('stock_qty', $take);
            $left -= $take;

            if ($left === 0) {
                break;
            }
        }
    }

    /**
     * Give $qty back to a product's stock.
     *
     * ponytail: returns to the product's first IN row, not the exact lot taken.
     * Add a so_realisasi pivot (like keluar_realisasi) if per-lokasi accuracy matters.
     */
    public static function release(int $productId, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $row = self::query()
            ->where('stock_type', 'IN')
            ->where('stock_id_product', $productId)
            ->orderBy('stock_id')
            ->lockForUpdate()
            ->first();

        $row?->increment('stock_qty', $qty);
    }
}
