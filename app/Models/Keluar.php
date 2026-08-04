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

    public static $filterColumns = ['out_code', 'out_reff', 'out_status'];
    public static $sortColumns   = ['out_code', 'out_tanggal', 'out_reff', 'out_qty', 'out_status'];

    protected $fillable = [
        'out_code',
        'out_reff',
        'out_tanggal',
        'out_status',
        'out_qty',
        'out_catatan',
        'out_assigned',
        'out_created_at',
        'out_created_by',
    ];

    protected $casts = [
        'out_tanggal'    => 'date',
        'out_qty'        => 'double',
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

    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar', 'out_code');
    }

    public function getDetailCountAttribute(): int
    {
        return $this->relationLoaded('details') ? $this->details->count() : $this->details()->count();
    }

    public function getPickedQtyAttribute(): float
    {
        if (!$this->relationLoaded('details')) {
            return (float) KeluarRealisasi::whereHas('detail', fn ($q) => $q->where('out_detail_code_keluar', $this->out_code))
                ->sum('out_realisasi_qty');
        }

        return (float) $this->details->sum(function ($d) {
            return $d->relationLoaded('realisasi') ? $d->realisasi->sum('out_realisasi_qty') : 0;
        });
    }

    public function getSoIdAttribute()
    {
        if ($this->relationLoaded('details')) {
            foreach ($this->details as $detail) {
                if ($detail->relationLoaded('soDetail') && $detail->soDetail?->so_detail_id_so) {
                    return $detail->soDetail->so_detail_id_so;
                }
            }
        }

        return \App\Models\KeluarDetail::where('out_detail_code_keluar', $this->out_code)
            ->whereNotNull('out_detail_id_so_detail')
            ->join('detail_so', 'out_detail_id_so_detail', '=', 'so_detail_id')
            ->value('so_detail_id_so');
    }
}
