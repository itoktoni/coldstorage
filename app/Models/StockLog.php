<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StockLog extends BaseModel
{
    protected $table = 'stock_log';
    protected $primaryKey = 'stock_log_id';
    public $timestamps = false;

    protected $fillable = [
        'stock_log_code',
        'stock_id',
        'stock_code',
        'stock_id_product',
        'stock_code_lokasi',
        'stock_type',
        'stock_qty',
        'stock_qty_before',
        'stock_qty_after',
        'action',
        'description',
        'stock_reff',
        'created_at',
    ];

    protected $casts = [
        'stock_qty'       => 'double',
        'stock_qty_before' => 'double',
        'stock_qty_after'  => 'double',
        'created_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            if (empty($log->stock_log_code)) {
                $log->stock_log_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'STL-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('stock_log_code', $code)->exists());

        return $code;
    }

    public static function field_name(): string
    {
        return 'stock_log_code';
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id', 'stock_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'stock_id_product', 'product_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'stock_code_lokasi', 'lokasi_code');
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

        $query = parent::scopeFilter($query, $params);

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

    public static function log(Stock $stock, string $action, ?float $qtyBefore = null, ?float $qtyAfter = null, ?string $description = null): self
    {
        return self::create([
            'stock_id'          => $stock->stock_id,
            'stock_code'        => $stock->stock_code,
            'stock_id_product'  => $stock->stock_id_product,
            'stock_code_lokasi' => $stock->stock_code_lokasi,
            'stock_type'        => $stock->stock_type,
            'stock_qty'         => $stock->stock_qty,
            'stock_qty_before'  => $qtyBefore,
            'stock_qty_after'   => $qtyAfter,
            'action'            => $action,
            'description'       => $description,
            'stock_reff'        => $stock->stock_reff,
        ]);
    }
}
