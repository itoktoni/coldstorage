<?php

namespace App\Models;

class MasukRealisasi extends BaseModel
{
    protected $table = 'masuk_realisasi';
    protected $primaryKey = 'in_realisasi_id';
    public $timestamps = true;

    public static $filterColumns = ['in_realisasi_masuk_code', 'in_realisasi_id_product', 'in_realisasi_id_lokasi'];
    public static $sortColumns   = ['in_realisasi_code', 'in_realisasi_id_product', 'in_realisasi_qty', 'in_realisasi_id_lokasi'];

    protected $fillable = [
        'in_realisasi_masuk_code',
        'in_realisasi_code',
        'in_realisasi_barcode',
        'in_realisasi_id_product',
        'in_realisasi_qty',
        'in_realisasi_id_lokasi',
        'in_realisasi_group',
    ];

    protected $casts = [
        'in_realisasi_qty'   => 'double',
        'in_realisasi_group' => 'integer',
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
        return $this->belongsTo(Lokasi::class, 'in_realisasi_id_lokasi', 'lokasi_id');
    }

    public function rules(): array
    {
        return [
            'in_realisasi_masuk_code' => ['required', 'string', 'exists:masuk_detail,in_detail_code'],
            'in_realisasi_code'       => ['nullable', 'string', 'max:50'],
            'in_realisasi_barcode'    => ['required', 'string'],
            'in_realisasi_id_product' => ['required', 'integer', 'exists:product,product_id'],
            'in_realisasi_qty'        => ['required', 'numeric', 'min:0.001'],
            'in_realisasi_id_lokasi'  => ['required', 'integer', 'exists:lokasi,lokasi_id'],
        ];
    }
}
