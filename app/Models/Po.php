<?php

namespace App\Models;

class Po extends BaseModel
{
    protected $table = 'po';
    protected $primaryKey = 'po_id';
    public $timestamps = true;

    const STATUS_PENDING = 'Pending';
    const STATUS_ORDERED = 'Ordered';
    const STATUS_PARTIAL = 'Partial';
    const STATUS_CLOSED  = 'Closed';

    public static $filterColumns = ['po_code', 'po_supplier', 'po_tanggal', 'po_status'];
    public static $sortColumns   = ['po_code', 'po_tanggal', 'po_supplier', 'po_status'];

    protected $fillable = [
        'po_tanggal',
        'po_code',
        'po_supplier',
        'po_status',
        'po_keterangan',
    ];

    protected $casts = [
        'po_tanggal' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PoDetail::class, 'po_detail_id_po', 'po_id');
    }
}
