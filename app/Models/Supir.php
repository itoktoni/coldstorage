<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supir extends Model
{
    protected $table = 'supir';

    protected $fillable = ['supir_nama', 'supir_telp', 'supir_aktif'];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id_supir');
    }
}
