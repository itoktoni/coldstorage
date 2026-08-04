<?php

namespace App\Models;

class Delivery extends BaseModel
{
    protected $table = 'delivery';

    protected $primaryKey = 'delivery_id';

    public $timestamps = true;

    protected $fillable = [
        'delivery_code',
        'delivery_tanggal',
        'delivery_id_so',
        'delivery_id_invoice',
        'delivery_nama_penerima',
        'delivery_alamat_tujuan',
        'delivery_nama_driver',
        'delivery_plat_kendaraan',
        'delivery_nama_kurir',
        'delivery_catatan',
        'delivery_status',
    ];

    protected $casts = [
        'delivery_tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            if (empty($delivery->delivery_code)) {
                $delivery->delivery_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'DO-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('delivery_code', $code)->exists());

        return $code;
    }

    public function so()
    {
        return $this->belongsTo(So::class, 'delivery_id_so', 'so_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'delivery_id_invoice', 'invoice_id');
    }
}
