<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class Stock extends BaseModel
{
    protected $table = 'stock';
    protected $primaryKey = 'stock_id';
    public $timestamps = true;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_RESERVE = 'RESERVE';
    public const TYPE_STAGING = 'STAGING';

    public static $filterColumns = ['stock_code', 'product_nama', 'stock_code_lokasi', 'stock_type'];
    public static $sortColumns   = ['stock_code', 'stock_id_product', 'stock_code_lokasi', 'stock_type', 'stock_qty'];

    protected $fillable = [
        'stock_code',
        'stock_id_product',
        'stock_code_lokasi',
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
            'stock_code_lokasi'  => ['nullable', 'exists:lokasi,lokasi_code'],
            'stock_qty'          => ['required', 'numeric', 'min:0'],
            'stock_expired_date' => ['nullable', 'date'],
            'stock_reff'         => ['nullable', 'string', 'max:100'],
            'stock_type'         => ['required', 'in:IN,OUT,RESERVE,STAGING'],
        ];
    }

    public static function field_name(): string
    {
        return 'product_nama';
    }

    public static function typeOptions(): array
    {
        return ['IN' => 'IN', 'OUT' => 'OUT', 'RESERVE' => 'RESERVE', 'STAGING' => 'STAGING'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'stock_id_product', 'product_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'stock_code_lokasi', 'lokasi_code');
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

    public function scopeFilter(Builder $query, array|null $params = null): Builder
    {
        if (!isset($params)) {
            $params = request()->query('filters', []);
        }

        $virtualColumns = ['product_nama', 'lokasi_nama'];
        $virtualFilters = [];

        foreach ($virtualColumns as $field) {
            if (isset($params[$field])) {
                $virtualFilters[$field] = $params[$field];
                unset($params[$field]);
            }
        }

        // Delegate to Purity for stock table columns (bootFilter runs here)
        $query = parent::scopeFilter($query, $params);

        // Apply virtual column filters as plain WHERE (already joined by getData)
        foreach ($virtualFilters as $field => $operators) {
            foreach ($operators as $operator => $value) {
                $sqlOperator = match ($operator) {
                    '$eq' => '=',
                    '$contains' => 'LIKE',
                    '$ne' => '!=',
                    '$gt' => '>',
                    '$gte' => '>=',
                    '$lt' => '<',
                    '$lte' => '<=',
                    default => 'LIKE',
                };

                $sqlValue = $sqlOperator === 'LIKE' ? '%'.$value.'%' : $value;
                $query->where($field, $sqlOperator, $sqlValue);
            }
        }

        return $query;
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

    /**
     * Reduce RESERVE rows (identified by comma-separated SO codes in $soCodes)
     * for a product once goods are actually picked from the rack.
     * Returns the qty that could be consumed.
     */
    public static function consumeReserve(string $soCodes, int $productId, float $qty): float
    {
        if ($qty <= 0 || trim($soCodes) === '') {
            return 0;
        }

        $codes = array_values(array_filter(array_map('trim', explode(',', $soCodes))));
        if (empty($codes)) {
            return 0;
        }

        $rows = self::query()
            ->where('stock_type', self::TYPE_RESERVE)
            ->whereIn('stock_reff', $codes)
            ->where('stock_id_product', $productId)
            ->where('stock_qty', '>', 0)
            ->orderBy('stock_id')
            ->lockForUpdate()
            ->get();

        $left = $qty;
        foreach ($rows as $row) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, (float) $row->stock_qty);
            $row->decrement('stock_qty', $take);
            $left -= $take;
        }

        return $qty - $left;
    }
}
