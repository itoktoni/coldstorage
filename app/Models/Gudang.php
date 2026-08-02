<?php

namespace App\Models;

use Illuminate\Validation\Rule;

class Gudang extends BaseModel
{
    protected $table = 'gudang';
    protected $primaryKey = 'gudang_code';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    public static $filterColumns = ['gudang_code', 'gudang_nama'];
    public static $sortColumns   = ['gudang_code', 'gudang_nama'];

    protected $fillable = [
        'gudang_code',
        'gudang_nama',
    ];

    public function lokasi()
    {
        return $this->hasMany(Lokasi::class, 'lokasi_code_gudang', 'gudang_code');
    }

    public static function field_name()
    {
        return 'gudang_nama';
    }

    public function rules(): array
    {
        return [
            'gudang_code' => ['required', 'string', 'max:50'],
            'gudang_nama' => [
                'required', 'string', 'max:100',
                Rule::unique('gudang', 'gudang_nama')->ignore($this->exists ? $this->gudang_code : null, 'gudang_code'),
            ],
        ];
    }
}
