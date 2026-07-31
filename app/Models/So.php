<?php

namespace App\Models;

class So extends BaseModel
{
    protected $table = 'so';

    protected $primaryKey = 'so_id';

    public $timestamps = true;

    const STATUS_PENDING = 'Pending';

    const STATUS_CONFIRMED = 'Confirmed';

    const STATUS_SHIPPED = 'Shipped';

    const STATUS_CLOSED = 'Closed';

    public static $filterColumns = ['so_code', 'so_id_customer', 'so_tanggal', 'so_status'];

    public static $sortColumns = ['so_code', 'so_tanggal', 'so_status'];

    protected $fillable = [
        'so_tanggal',
        'so_code',
        'so_id_customer',
        'so_status',
        'so_keterangan',
    ];

    protected $attributes = [
        'so_status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'so_tanggal' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(SoDetail::class, 'so_detail_id_so', 'so_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'so_id_customer', 'customer_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $so) {
            if (empty($so->so_code)) {
                $so->so_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'SO-'.now()->format('Ymd').'-'.unic_number(4);
        } while (self::where('so_code', $code)->exists());

        return $code;
    }

    public static function customerOptions(): array
    {
        return Customer::pluck('customer_nama', 'customer_id')->all();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING   => self::STATUS_PENDING,
            self::STATUS_CONFIRMED => self::STATUS_CONFIRMED,
            self::STATUS_SHIPPED   => self::STATUS_SHIPPED,
            self::STATUS_CLOSED    => self::STATUS_CLOSED,
        ];
    }

    public function getSoTotalAttribute(): string
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->get();

        return (string) $details->sum(fn ($d) => (int) $d->so_detail_qty * (float) $d->so_detail_harga);
    }

    public function rules(): array
    {
        return [
            'so_code'       => ['nullable', 'string', 'max:50'],
            'so_tanggal'    => ['required', 'date'],
            'so_id_customer' => ['required', 'integer', 'exists:customer,customer_id'],
            'so_status'     => ['nullable', 'string', 'in:Pending,Confirmed,Shipped,Closed'],
            'so_keterangan' => ['nullable', 'string'],
            'details'                        => ['required', 'array', 'min:1'],
            'details.*.so_detail_id'         => ['nullable', 'integer'],
            'details.*.so_detail_id_product' => ['required', 'integer', 'exists:product,product_id'],
            'details.*.so_detail_qty'        => ['required', 'integer', 'min:1'],
        ];
    }
}
