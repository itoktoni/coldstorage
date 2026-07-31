<?php

namespace App\Models;

class Customer extends BaseModel
{
    protected $table = 'customer';

    protected $primaryKey = 'customer_id';

    public $timestamps = true;

    public static $filterColumns = ['customer_nama', 'customer_telepon'];

    public static $sortColumns = ['customer_nama', 'customer_telepon'];

    protected $fillable = [
        'customer_nama',
        'customer_telepon',
        'customer_alamat',
    ];

    public static function field_name()
    {
        return 'customer_nama';
    }

    public function rules(): array
    {
        return [
            'customer_nama'    => ['required', 'string', 'max:200'],
            'customer_telepon' => ['nullable', 'string', 'max:30'],
            'customer_alamat'  => ['nullable', 'string'],
        ];
    }
}
