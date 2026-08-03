<?php

namespace App\Models;

class KeluarRealisasi extends BaseModel
{
    protected $table = 'keluar_realisasi';
    protected $primaryKey = 'out_realisasi_id';
    public $timestamps = true;

    public static $filterColumns = ['out_realisasi_code', 'out_realisasi_id_detail', 'out_realisasi_id_stock'];
    public static $sortColumns   = ['out_realisasi_code', 'out_realisasi_id_detail', 'out_realisasi_qty', 'out_realisasi_id_stock'];

    protected $fillable = [
        'out_realisasi_id_detail',
        'out_realisasi_code',
        'out_realisasi_qty',
        'out_realisasi_id_stock',
    ];

    protected $casts = [
        'out_realisasi_qty' => 'double',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $row) {
            if (empty($row->out_realisasi_code)) {
                $row->out_realisasi_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'OUTR-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('out_realisasi_code', $code)->exists());

        return $code;
    }

    public function detail()
    {
        return $this->belongsTo(KeluarDetail::class, 'out_realisasi_id_detail', 'out_detail_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'out_realisasi_id_stock', 'stock_id');
    }

    public function getDetailCodeAttribute(): ?string
    {
        return $this->relationLoaded('detail') ? ($this->detail->out_detail_code ?? null) : null;
    }

    public function getStockCodeAttribute(): ?string
    {
        return $this->relationLoaded('stock') ? ($this->stock->stock_code ?? null) : null;
    }

    public function getLokasiAttribute(): ?string
    {
        return $this->relationLoaded('stock') ? ($this->stock->stock_code_lokasi ?? null) : null;
    }
}
