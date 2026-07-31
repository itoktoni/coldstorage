<?php

namespace App\Models;

class MasukDetail extends BaseModel
{
    protected $table = 'masuk_detail';
    protected $primaryKey = 'in_detail_code';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    const STATUS_PENDING     = 'Pending';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_DONE        = 'Done';

    public static $filterColumns = ['in_detail_code', 'in_detail_status', 'in_detail_id_product'];
    public static $sortColumns   = ['in_detail_tanggal', 'in_detail_status'];

    protected $fillable = [
        'in_detail_code',
        'in_detail_reff',
        'in_detail_tanggal',
        'in_detail_status',
        'in_detail_catatan',
        'in_detail_created_at',
        'in_detail_created_by',
        'in_detail_id_product',
        'in_detail_qty',
    ];

    protected $casts = [
        'in_detail_tanggal'    => 'date',
        'in_detail_created_at' => 'datetime',
        'in_detail_qty'        => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'in_detail_id_product', 'product_id');
    }

    public function realisasi()
    {
        return $this->hasMany(MasukRealisasi::class, 'in_realisasi_masuk_code', 'in_detail_code');
    }
}
