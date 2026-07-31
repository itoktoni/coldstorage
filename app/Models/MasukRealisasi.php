<?php

namespace App\Models;

class MasukRealisasi extends BaseModel
{
    protected $table = 'masuk_realisasi';
    protected $primaryKey = 'in_realisasi_id';
    public $timestamps = true;

    public static $filterColumns = ['in_realisasi_masuk_code', 'in_realisasi_id_product'];
    public static $sortColumns   = ['in_realisasi_id'];

    protected $fillable = [
        'in_realisasi_masuk_code',
        'in_realisasi_code',
        'in_realisasi_id_product',
        'in_realisasi_qty',
        'in_realisasi_group',
    ];

    protected $casts = [
        'in_realisasi_qty'   => 'integer',
        'in_realisasi_group' => 'integer',
    ];

    public function masukDetail()
    {
        return $this->belongsTo(MasukDetail::class, 'in_realisasi_masuk_code', 'in_detail_code');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'in_realisasi_id_product', 'product_id');
    }
}
