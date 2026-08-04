<?php

namespace App\Models;

class SoPrepareDetail extends BaseModel
{
    protected $table = 'so_prepare_detail';

    protected $primaryKey = 'so_prepare_detail_id';

    public $timestamps = true;

    protected $fillable = [
        'so_prepare_detail_id_prepare',
        'so_prepare_detail_id_realisasi',
        'so_prepare_detail_id_product',
        'so_prepare_detail_id_stock',
        'so_prepare_detail_qty',
    ];

    protected $casts = [
        'so_prepare_detail_qty' => 'double',
    ];

    public function prepare()
    {
        return $this->belongsTo(SoPrepare::class, 'so_prepare_detail_id_prepare', 'so_prepare_id');
    }

    public function realisasi()
    {
        return $this->belongsTo(KeluarRealisasi::class, 'so_prepare_detail_id_realisasi', 'out_realisasi_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'so_prepare_detail_id_product', 'product_id');
    }
}
