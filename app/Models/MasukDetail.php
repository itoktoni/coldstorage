<?php

namespace App\Models;

use App\Concerns\HasUserstamps;
use App\Wms\MasukStatusEnum;

class MasukDetail extends BaseModel
{
    use HasUserstamps;
    protected $table = 'masuk_detail';
    protected $primaryKey = 'in_detail_code';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    const CREATED_BY = 'in_detail_created_by';
    const UPDATED_BY = 'in_detail_updated_by';

    public static $filterColumns = ['in_detail_code', 'in_detail_status', 'in_detail_id_product'];
    public static $sortColumns   = ['in_detail_code', 'in_detail_reff', 'in_detail_tanggal', 'in_detail_status'];

    protected $fillable = [
        'in_detail_code',
        'in_detail_reff',
        'in_detail_tanggal',
        'in_detail_status',
        'in_detail_catatan',
        'in_detail_created_at',
        'in_detail_created_by',
        'in_detail_updated_by',
        'in_detail_id_product',
        'in_detail_qty',
    ];

    protected $casts = [
        'in_detail_tanggal'    => 'date',
        'in_detail_created_at' => 'datetime',
        'in_detail_qty'        => 'double',
        'in_detail_status'     => MasukStatusEnum::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'in_detail_id_product', 'product_id');
    }

    public function poDetail()
    {
        return $this->belongsTo(PoDetail::class, 'in_detail_reff', 'po_detail_code');
    }

    public function realisasi()
    {
        return $this->hasMany(MasukRealisasi::class, 'in_realisasi_masuk_code', 'in_detail_code');
    }

    public function getSupplierNamaAttribute(): string
    {
        return optional(optional($this->poDetail)->po)->supplier->supplier_nama ?? '-';
    }

    protected static function booted(): void
    {
        static::creating(function (self $detail) {
            if (empty($detail->in_detail_code)) {
                $detail->in_detail_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'IN-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('in_detail_code', $code)->exists());

        return $code;
    }

    public static function statusOptions(): array
    {
        return MasukStatusEnum::getOptions();
    }

    public function rules(): array
    {
        return [
            'in_detail_code'       => ['nullable', 'string', 'max:50'],
            'in_detail_reff'       => ['nullable', 'string', 'max:100'],
            'in_detail_tanggal'    => ['required', 'date'],
            'in_detail_status'     => ['nullable', 'string', 'in:pending,process,ready,complete'],
            'in_detail_catatan'    => ['nullable', 'string'],
            'in_detail_id_product' => ['required', 'exists:product,product_id'],
            'in_detail_qty'        => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
