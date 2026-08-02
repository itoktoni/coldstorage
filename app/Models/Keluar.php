<?php

namespace App\Models;

class Keluar extends BaseModel
{
    protected $table = 'keluar';
    protected $primaryKey = 'out_code';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    const STATUS_PENDING     = 'Pending';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_DONE        = 'Done';

    public static $filterColumns = ['out_code', 'out_status'];
    public static $sortColumns   = ['out_tanggal', 'out_status'];

    protected $fillable = [
        'out_code',
        'out_reff',
        'out_tanggal',
        'out_status',
        'out_catatan',
        'out_created_at',
        'out_created_by',
    ];

    protected $casts = [
        'out_tanggal'    => 'date',
        'out_created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $keluar) {
            if (empty($keluar->out_code)) {
                $keluar->out_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'OUT-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('out_code', $code)->exists());

        return $code;
    }

    public function details()
    {
        return $this->hasMany(KeluarDetail::class, 'out_detail_code_keluar', 'out_code');
    }
}
