<?php

namespace App\Models;

class Lokasi extends BaseModel
{
    protected $table = 'lokasi';
    protected $primaryKey = 'lokasi_id';
    public $timestamps = true;

    public static $filterColumns = ['lokasi_nama', 'lokasi_id_gudang'];
    public static $sortColumns   = ['gudang_nama', 'lokasi_nama'];

    protected $fillable = [
        'lokasi_nama',
        'lokasi_id_gudang',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'lokasi_id_gudang', 'gudang_id');
    }

    public function getGudangNamaAttribute()
    {
        return $this->gudang?->gudang_nama;
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'stock_id_lokasi', 'lokasi_id');
    }
}
