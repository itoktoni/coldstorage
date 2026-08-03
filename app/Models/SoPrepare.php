<?php

namespace App\Models;

class SoPrepare extends BaseModel
{
    protected $table = 'so_prepare';
    protected $primaryKey = 'so_prepare_id';
    public $timestamps = true;

    const STATUS_PENDING = 'Pending';
    const STATUS_DONE    = 'Done';

    protected $fillable = [
        'so_prepare_id_so',
        'so_prepare_code',
        'so_prepare_id_keluar',
        'so_prepare_status',
    ];

    protected $attributes = [
        'so_prepare_status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $prepare) {
            if (empty($prepare->so_prepare_code)) {
                $prepare->so_prepare_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'SPR-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('so_prepare_code', $code)->exists());

        return $code;
    }

    public function so()
    {
        return $this->belongsTo(So::class, 'so_prepare_id_so', 'so_id');
    }

    public function keluar()
    {
        return $this->belongsTo(Keluar::class, 'so_prepare_id_keluar', 'out_code');
    }

    public function details()
    {
        return $this->hasMany(SoPrepareDetail::class, 'so_prepare_detail_id_prepare', 'so_prepare_id');
    }
}
