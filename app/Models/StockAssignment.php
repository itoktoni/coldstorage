<?php

namespace App\Models;

class StockAssignment extends BaseModel
{
    protected $table = 'stock_assignment';
    protected $primaryKey = 'stock_assignment_id';

    protected $fillable = [
        'stock_assignment_id_keluar',
        'stock_assignment_id_stock',
        'stock_assignment_id_keluar_detail',
        'stock_assignment_id_so_detail',
        'stock_assignment_qty',
        'stock_assignment_status',
        'stock_assignment_notes',
    ];

    protected $casts = [
        'stock_assignment_qty' => 'float',
    ];

    public static $filterColumns = [];
    public static $sortColumns   = [];

    public function keluar()
    {
        return $this->belongsTo(Keluar::class, 'stock_assignment_id_keluar', 'out_code');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_assignment_id_stock');
    }

    public function keluarDetail()
    {
        return $this->belongsTo(KeluarDetail::class, 'stock_assignment_id_keluar_detail');
    }

    public function soDetail()
    {
        return $this->belongsTo(SoDetail::class, 'stock_assignment_id_so_detail');
    }
}
