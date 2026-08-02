<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class MasukRealisasi extends BaseModel
{
    protected $table = 'masuk_realisasi';
    protected $primaryKey = 'in_realisasi_id';
    public $timestamps = true;

    public static $filterColumns = ['in_realisasi_masuk_code', 'in_realisasi_id_product', 'in_realisasi_code_lokasi', 'in_detail_status'];
    public static $sortColumns   = ['in_realisasi_code', 'in_realisasi_id_product', 'in_realisasi_qty', 'in_realisasi_code_lokasi', 'in_detail_status'];

    protected $fillable = [
        'in_realisasi_masuk_code',
        'in_realisasi_code',
        'in_realisasi_id_product',
        'in_realisasi_qty',
        'in_realisasi_code_lokasi',
        'in_realisasi_barcode',
        'in_realisasi_group',
    ];

    protected $casts = [
        'in_realisasi_qty'   => 'double',
        'in_realisasi_group' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $realisasi) {
            if (empty($realisasi->in_realisasi_code)) {
                $realisasi->in_realisasi_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'INR-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('in_realisasi_code', $code)->exists());

        return $code;
    }

    public function masukDetail()
    {
        return $this->belongsTo(MasukDetail::class, 'in_realisasi_masuk_code', 'in_detail_code');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'in_realisasi_id_product', 'product_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'in_realisasi_code_lokasi', 'lokasi_code');
    }

    public static function generateGroupCode(): string
    {
        do {
            $code = 'PAL-'.now()->format('Ymd').'-'.unic_number(6);
        } while (self::where('in_realisasi_group', $code)->exists());

        return $code;
    }

    public function rules(): array
    {
        return [
            'in_realisasi_masuk_code' => ['required', 'string', 'exists:masuk_detail,in_detail_code'],
            'in_realisasi_code'       => ['nullable', 'string', 'max:50'],
            'in_realisasi_barcode'    => ['required', 'string'],
            'in_realisasi_id_product' => ['required', 'integer', 'exists:product,product_id'],
            'in_realisasi_qty'        => ['required', 'numeric', 'min:0.001'],
            'in_realisasi_code_lokasi' => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ];
    }

    public static function statusOptions(): array
    {
        return ['pending' => 'Pending', 'process' => 'Process', 'ready' => 'Ready', 'complete' => 'Complete'];
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->in_detail_status) {
            'pending' => 'default',
            'process' => 'warning',
            'ready' => 'info',
            'complete' => 'success',
            default => 'default',
        };
    }

    public function scopeFilter(Builder $query, array|null $params = null): Builder
    {
        if (!isset($params)) {
            $params = request()->query('filters', []);
        }

        $virtualColumns = ['in_detail_status'];
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
                    default => '=',
                };

                $query->where('masuk_detail.'.$field, $sqlOperator, $value);
            }
        }

        return $query;
    }
}
