<?php

namespace App\Models;

class SplitTarget extends BaseModel
{
    protected $table = 'split_target';

    protected $primaryKey = 'split_target_id';

    public $timestamps = true;

    protected $fillable = [
        'split_target_id_split',
        'split_target_id_product',
        'split_target_qty',
        'split_target_jumlah',
        'split_target_urutan',
    ];

    protected $casts = [
        'split_target_qty' => 'double',
        'split_target_jumlah' => 'integer',
        'split_target_urutan' => 'integer',
    ];

    public function split()
    {
        return $this->belongsTo(Split::class, 'split_target_id_split', 'split_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'split_target_id_product', 'product_id');
    }
}
