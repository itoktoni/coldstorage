<?php

namespace App\Models;

class SplitDetail extends BaseModel
{
    protected $table = 'split_detail';
    protected $primaryKey = 'split_detail_id';
    public $timestamps = true;

    protected $fillable = [
        'split_detail_id_split',
        'split_detail_id_stock',
        'split_detail_qty',
    ];

    protected $casts = [
        'split_detail_qty' => 'double',
    ];

    public function split()
    {
        return $this->belongsTo(Split::class, 'split_detail_id_split', 'split_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'split_detail_id_stock', 'stock_id');
    }
}
