<?php

namespace App\Models;

use App\Models\BaseModel;

class Satuan extends BaseModel
{
    protected $table = 'satuan';
    protected $keyType = 'int';
    protected $primaryKey = 'satuan_id';

    public $timestamps = false;
    public $incrementing = true;

    /**
     * Columns available for filtering.
     */
    public static $filterColumns = [
        'satuan_code' => 'Code',
        'satuan_nama' => 'Nama'
    ];

    /**
     * Columns available for sorting.
     */
    public static $sortColumns = [
        'satuan_code',
        'satuan_nama'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'satuan_code',
        'satuan_nama'
    ];

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            
			'satuan_code' => 'required|string',
			'satuan_nama' => 'string',
        ];
    }

    public function toArray(){}

    public static function field_name()
    {
        return 'satuan_nama';
    }

}
