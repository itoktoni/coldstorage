<?php

namespace App\Models;

class Gudang extends BaseModel
{
    protected $table = 'gudang';
    protected $primaryKey = 'gudang_id';
    public $timestamps = true;

    public static $filterColumns = ['gudang_nama'];
    public static $sortColumns   = ['gudang_nama'];

    protected $fillable = [
        'gudang_nama',
    ];

    public function lokasi()
    {
        return $this->hasMany(Lokasi::class, 'lokasi_id_gudang', 'gudang_id');
    }
}
