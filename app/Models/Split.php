<?php

namespace App\Models;

class Split extends BaseModel
{
    protected $table = 'split';

    protected $primaryKey = 'split_id';

    public $timestamps = true;

    public static $filterColumns = ['split_id_product_source', 'split_id_product_target', 'split_id_product_waste', 'split_status', 'split_tanggal'];

    public static $sortColumns = ['split_tanggal', 'split_status', 'split_id'];

    protected $fillable = [
        'split_id_product_source',
        'split_id_product_target',
        'split_id_product_waste',
        'split_qty_hasil',
        'split_qty_waste',
        'split_qty_penyusutan',
        'split_status',
        'split_tanggal',
        'split_created_by',
        'split_created_at',
    ];

    protected $casts = [
        'split_qty_hasil' => 'double',
        'split_qty_waste' => 'double',
        'split_qty_penyusutan' => 'double',
        'split_tanggal' => 'date',
        'split_created_at' => 'datetime',
    ];

    public function productSource()
    {
        return $this->belongsTo(Product::class, 'split_id_product_source', 'product_id');
    }

    public function productTarget()
    {
        return $this->belongsTo(Product::class, 'split_id_product_target', 'product_id');
    }

    public function productWaste()
    {
        return $this->belongsTo(Product::class, 'split_id_product_waste', 'product_id');
    }

    public function details()
    {
        return $this->hasMany(SplitDetail::class, 'split_detail_id_split', 'split_id');
    }

    public function rules(): array
    {
        $id = $this->exists ? $this->split_id : null;

        return [
            'split_id_product_source' => ['required', 'exists:product,product_id'],
            'split_id_product_target' => ['required', 'exists:product,product_id'],
            'split_id_product_waste' => ['nullable', 'exists:product,product_id'],
            'split_qty_hasil' => ['required', 'numeric', 'min:0'],
            'split_qty_waste' => ['required', 'numeric', 'min:0'],
            'split_tanggal' => ['required', 'date'],
            'split_status' => ['nullable', 'string', 'max:20'],
        ];
    }
}
