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

    protected $attributes = [
        'po_status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'po_tanggal' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PoDetail::class, 'po_detail_id_po', 'po_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $po) {
            if (empty($po->po_code)) {
                $po->po_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'PO-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('po_code', $code)->exists());

        return $code;
    }

    public static function supplierOptions(): array
    {
        return Supplier::pluck('supplier_nama', 'supplier_nama')->all();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => self::STATUS_PENDING,
            self::STATUS_ORDERED => self::STATUS_ORDERED,
            self::STATUS_PARTIAL => self::STATUS_PARTIAL,
            self::STATUS_CLOSED  => self::STATUS_CLOSED,
        ];
    }

    public function rules(): array
    {
        return [
            'po_code'       => ['nullable', 'string', 'max:50'],
            'po_tanggal'    => ['required'],
            'po_supplier'   => ['required', 'string', 'max:200', 'exists:supplier,supplier_nama'],
            'po_status'     => ['nullable', 'string', 'in:Pending,Ordered,Partial,Closed'],
            'po_keterangan' => ['nullable', 'string'],
            'details'                         => ['required', 'array', 'min:1'],
            'details.*.po_detail_id'          => ['nullable', 'integer'],
            'details.*.po_detail_id_product'  => ['required', 'integer', 'exists:product,product_id'],
            'details.*.po_detail_qty'         => ['required', 'integer', 'min:1'],
        ];
    }
}
