<?php

namespace App\Models;

use App\Models\BaseModel;

class Supplier extends BaseModel
{
    protected $table = 'supplier';
    protected $keyType = 'int';
    protected $primaryKey = 'supplier_id';

    public $timestamps = false;
    public $incrementing = true;

    /**
     * Columns available for filtering.
     */
    public static $filterColumns = [
        'supplier_id' => 'Id',
        'supplier_nama' => 'Nama'
    ];

    /**
     * Columns available for sorting.
     */
    public static $sortColumns = [
        'supplier_id',
        'supplier_nama'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'supplier_nama'
    ];

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

			'supplier_nama' => 'required|string'

        ];
    }

    public function toArray(){}

    public static function field_name()
    {
        return 'supplier_nama';
    }

}
