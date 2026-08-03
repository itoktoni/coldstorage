<?php

namespace App\Models;

class KeluarDetail extends BaseModel
{
    protected $table = 'keluar_detail';
    protected $primaryKey = 'out_detail_id';
    public $timestamps = true;

    public static $filterColumns = ['out_detail_code', 'out_detail_code_keluar', 'out_detail_id_product'];
    public static $sortColumns   = ['out_detail_code', 'out_detail_code_keluar', 'out_detail_id_product', 'out_detail_qty', 'out_detail_reff'];

    protected $fillable = [
        'out_detail_code_keluar',
        'out_detail_id_product',
        'out_detail_id_so_detail',
        'out_detail_code',
        'out_detail_qty',
        'out_detail_reff',
    ];

    protected $casts = [
        'out_detail_qty' => 'double',
    ];

    public function keluar()
    {
        return $this->belongsTo(Keluar::class, 'out_detail_code_keluar', 'out_code');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'out_detail_id_product', 'product_id');
    }

    public function soDetail()
    {
        return $this->belongsTo(SoDetail::class, 'out_detail_id_so_detail', 'so_detail_id');
    }

    public function realisasi()
    {
        return $this->hasMany(KeluarRealisasi::class, 'out_realisasi_id_detail', 'out_detail_id');
    }

    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar_detail');
    }

    public function getProductNamaAttribute(): ?string
    {
        return $this->relationLoaded('product') ? ($this->product->product_nama ?? null) : null;
    }

    public function getSoCodeAttribute(): ?string
    {
        if (!$this->relationLoaded('soDetail')) {
            return $this->out_detail_reff;
        }
        return $this->soDetail?->so?->so_code ?? $this->out_detail_reff;
    }

    public function getPickedQtyAttribute(): float
    {
        if ($this->relationLoaded('realisasi')) {
            return (float) $this->realisasi->sum('out_realisasi_qty');
        }
        return (float) KeluarRealisasi::where('out_realisasi_id_detail', $this->out_detail_id)->sum('out_realisasi_qty');
    }
}
