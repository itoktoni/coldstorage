<?php

namespace App\Models;

class ForkliftTask extends BaseModel
{
    protected $table = 'forklift_task';
    protected $primaryKey = 'forklift_id';

    protected $fillable = [
        'forklift_type', 'forklift_pallet_code', 'forklift_lokasi_asal', 'forklift_lokasi_tujuan',
        'forklift_lokasi_final', 'forklift_reff', 'forklift_status', 'forklift_operator',
        'forklift_scan_asal_at', 'forklift_scan_tujuan_at',
    ];

    protected $casts = [
        'forklift_scan_asal_at'   => 'datetime',
        'forklift_scan_tujuan_at' => 'datetime',
    ];

    const TYPE_PUTAWAY = 'putaway';
    const TYPE_PICK    = 'pick';
    const STATUS_PENDING  = 'Pending';
    const STATUS_PROGRESS = 'Progress';
    const STATUS_DONE     = 'Done';

    public function lokasiAsal()
    {
        return $this->belongsTo(Lokasi::class, 'forklift_lokasi_asal', 'lokasi_code');
    }

    public function lokasiTujuan()
    {
        return $this->belongsTo(Lokasi::class, 'forklift_lokasi_tujuan', 'lokasi_code');
    }
}