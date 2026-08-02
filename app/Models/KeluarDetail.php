<?php

namespace App\Models;

class KeluarDetail extends BaseModel
{
    protected $table = 'keluar_detail';
    protected $primaryKey = 'out_detail_id';
    public $timestamps = true;

    public static $filterColumns = ['out_detail_code_keluar', 'out_detail_id_product'];
    public static $sortColumns   = ['out_detail_id'];

    protected $fillable = [
        'out_detail_code_keluar',
        'out_detail_id_product',
        'out_detail_code',
        'out_detail_qty',
        'out_detail_reff',
    ];

    protected $casts = [
        'out_detail_qty' => 'double',
    ];

    public function keluar()
    {
        return $this->belongsTo(Keluar::class, 'out_detail_code_keluar', 'out_code');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'out_detail_id_product', 'product_id');
    }

    public function realisasi()
    {
        return $this->hasMany(KeluarRealisasi::class, 'out_realisasi_id_detail', 'out_detail_id');
    }
}
