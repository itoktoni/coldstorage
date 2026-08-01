<?php

namespace App\Models;

use Illuminate\Validation\Rule;

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

    public static function field_name()
    {
        return 'gudang_nama';
    }

    public function rules(): array
    {
        return [
            'gudang_nama' => [
                'required', 'string', 'max:100',
                Rule::unique('gudang', 'gudang_nama')->ignore($this->exists ? $this->gudang_id : null, 'gudang_id'),
            ],
        ];
    }
}
