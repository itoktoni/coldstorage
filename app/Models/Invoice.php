<?php

namespace App\Models;

class Invoice extends BaseModel
{
    protected $table = 'invoice';

    protected $primaryKey = 'invoice_id';

    public $timestamps = true;

    protected $fillable = [
        'invoice_code',
        'invoice_tanggal',
        'invoice_id_so',
        'invoice_id_customer',
        'invoice_subtotal',
        'invoice_ppn',
        'invoice_total',
        'invoice_status',
        'invoice_keterangan',
    ];

    protected $casts = [
        'invoice_tanggal' => 'date',
        'invoice_subtotal' => 'decimal:2',
        'invoice_ppn' => 'decimal:2',
        'invoice_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_code)) {
                $invoice->invoice_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'INV-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('invoice_code', $code)->exists());

        return $code;
    }

    public function so()
    {
        return $this->belongsTo(So::class, 'invoice_id_so', 'so_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'invoice_id_customer', 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_detail_id_invoice', 'invoice_id');
    }
}
