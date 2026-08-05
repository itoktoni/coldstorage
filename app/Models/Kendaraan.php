<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    protected $table = 'kendaraan';

    protected $fillable = ['kendaraan_nama', 'kendaraan_plat', 'kendaraan_tipe', 'kendaraan_aktif'];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id_kendaraan');
    }
}
