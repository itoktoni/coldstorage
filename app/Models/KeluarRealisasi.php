<?php

namespace App\Models;

class KeluarRealisasi extends BaseModel
{
    protected $table = 'keluar_realisasi';
    protected $primaryKey = 'out_realisasi_id';
    public $timestamps = true;

    public static $filterColumns = ['out_realisasi_id_detail', 'out_realisasi_id_stock'];
    public static $sortColumns   = ['out_realisasi_id'];

    protected $fillable = [
        'out_realisasi_id_detail',
        'out_realisasi_code',
        'out_realisasi_id_stock',
    ];

    public function detail()
    {
        return $this->belongsTo(KeluarDetail::class, 'out_realisasi_id_detail', 'out_detail_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'out_realisasi_id_stock', 'stock_id');
    }
}
