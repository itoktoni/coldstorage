# Stock Assignment untuk Prepare SO

## Overview

Fitur agar coordinator bisa cherry-pick stock spesifik (per barcode/lokasi) untuk tiap SO detail sebelum forklift mengambil. Forklift mendapat instruksi pasti: ambil stock mana dari rak mana. Jika ada kendala, forklift komplain ke coordinator untuk re-assign.

## Problem

Saat ini forklift melihat "suggested stocks" (FIFO) tapi bisa pick stock mana saja. Di gudang besar, forklift perlu tahu persis: "Ambil STK-001 dari Rak B1, 20 unit". Tanpa pre-assign, forklift bisa salah ambil stock yang seharusnya untuk SO lain.

## Design Decisions

1. **RESERVE tetap virtual** — RESERVE hanya placeholder untuk hitung available stock (IN - RESERVE). Tidak diubah.
2. **stock_assignment = layer tambahan** — Mapping stock fisik ke SO detail. Instruksi forklift.
3. **1 stock bisa untuk beberapa SO** — STK-001 (30 unit) bisa di-split: 20 untuk SO-001, 10 untuk SO-002.

## Flow

```
1. Buat SO → RESERVE dibuat (virtual, per product, reff=so_code)

2. Pilih SO → postPrepare → Keluar + KeluarDetail dibuat, SO → Prepare

3. Coordinator assign stock (stock_assignment):
   - Buka halaman assign per-Keluar
   - Lihat: "SO-001 perlu 20 unit Product A"
   - Pilih: STK-001 dari Rak B1, 20 unit
   - Lihat: "SO-002 perlu 70 unit Product A"
   - Pilih: STK-001 sisa 10 + STK-002 25 + STK-003 35
   - Submit

4. Forklift pick:
   - Buka pick list → lihat instruksi: "Ambil STK-001 dari Rak B1, 20 unit"
   - Jika kendala → tombol "Komplain" → coordinator re-assign
   - Ambil → stock IN → STAGING
   - Buat keluar_realisasi
   - stock_assignment.status → Picked

5. Allocate (postPrepareSo):
   - Scan barcode staging → alokasikan ke SO
   - Tidak berubah dari flow saat ini

6. SO selesai → RESERVE dihapus
```

## Database Changes

### Table baru: `stock_assignment`

```php
Schema::create('stock_assignment', function (Blueprint $table) {
    $table->id('stock_assignment_id');
    $table->string('stock_assignment_id_keluar', 255);           // FK → keluar.out_code
    $table->unsignedBigInteger('stock_assignment_id_stock');      // FK → stock.stock_id
    $table->unsignedBigInteger('stock_assignment_id_keluar_detail'); // FK → keluar_detail.out_detail_id
    $table->unsignedBigInteger('stock_assignment_id_so_detail');  // FK → detail_so.so_detail_id
    $table->decimal('stock_assignment_qty', 15, 3);
    $table->enum('stock_assignment_status', ['Pending', 'Picked', 'Override'])->default('Pending');
    $table->text('stock_assignment_notes')->nullable();           // catatan override
    $table->timestamps();

    $table->foreign('stock_assignment_id_keluar')->references('out_code')->on('keluar')->onDelete('cascade');
    $table->foreign('stock_assignment_id_stock')->references('stock_id')->on('stock')->onDelete('cascade');
    $table->foreign('stock_assignment_id_keluar_detail')->references('out_detail_id')->on('keluar_detail')->onDelete('cascade');
    $table->foreign('stock_assignment_id_so_detail')->references('so_detail_id')->on('detail_so')->onDelete('cascade');
});
```

### Kolom baru di `keluar`: `out_assigned`

```php
$table->boolean('out_assigned')->default(false); // true jika semua stock sudah di-assign
```

## Relasi

```
keluar (1) ──── (N) stock_assignment
keluar_detail (1) ──── (N) stock_assignment
stock (1) ──── (N) stock_assignment
detail_so (1) ──── (N) stock_assignment

stock_assignment.status:
  Pending  → belum di-pick forklift
  Picked   → forklift sudah ambil (keluar_realisasi dibuat)
  Override → forklift tidak bisa ambil, coordinator re-assign
```

## Model Changes

### `StockAssignment` (baru)

```php
class StockAssignment extends BaseModel
{
    protected $table = 'stock_assignment';
    protected $primaryKey = 'stock_assignment_id';
    protected $fillable = [
        'stock_assignment_id_keluar',
        'stock_assignment_id_stock',
        'stock_assignment_id_keluar_detail',
        'stock_assignment_id_so_detail',
        'stock_assignment_qty',
        'stock_assignment_status',
        'stock_assignment_notes',
    ];
    protected $casts = [
        'stock_assignment_qty' => 'float',
    ];

    public function keluar() { return $this->belongsTo(Keluar::class, 'stock_assignment_id_keluar', 'out_code'); }
    public function stock() { return $this->belongsTo(Stock::class, 'stock_assignment_id_stock'); }
    public function keluarDetail() { return $this->belongsTo(KeluarDetail::class, 'stock_assignment_id_keluar_detail'); }
    public function soDetail() { return $this->belongsTo(SoDetail::class, 'stock_assignment_id_so_detail'); }
}
```

### `Keluar` — tambah relasi

```php
public function assignments() { return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar', 'out_code'); }
```

### `KeluarDetail` — tambah relasi

```php
public function assignments() { return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar_detail'); }
```

### `Stock` — tambah relasi

```php
public function assignments() { return $this->hasMany(StockAssignment::class, 'stock_assignment_id_stock'); }
```

### `SoDetail` — tambah relasi

```php
public function assignments() { return $this->hasMany(StockAssignment::class, 'stock_assignment_id_so_detail'); }
```

## Routes

```
GET  /wms/so-prepare/{soId}/assign  → SoController@getAssign   → name('wms-so-prepare.assign')
POST /wms/so-prepare/{soId}/assign  → SoController@postAssign  → name('wms-so-prepare.assignStore')
```

## Controller Changes

### `SoController::getAssign($soId)`

- Load SO + details + product
- Load Keluar untuk SO ini (via `keluarDetail.soDetail.so`)
- Load available stock: `Stock::where('stock_type', 'IN')->where('stock_qty', '>', 0)`
- **Hitung sisa tersedia per stock**: `stock_qty - sum(assigned qty untuk stock yang sama, status Pending/Picked)` — agar coordinator tahu berapa sisa yang bisa di-assign
- Load existing assignments
- Return view `pages.so.assign`

### `SoController::postAssign($soId)`

- Validate: assignments array (stock_id, keluar_detail_id, qty)
- Validate: qty <= available stock qty, qty <= remaining need
- Delete existing assignments untuk keluar ini (replace)
- Create new `StockAssignment` rows
- Set `keluar.out_assigned = true`

### `ForkliftController::pick($outCode)`

- Load assignments untuk keluar ini
- Group by keluar_detail
- For each detail: show assigned stocks with barcode, lokasi, qty
- If no assignments: fall back to FIFO suggestions (backward compatible)

### `ForkliftController::pickStore($outCode)`

- When picking: check if scanned stock matches assignment
- If matches → create keluar_realisasi, set assignment status = Picked
- If doesn't match → check if user has override permission, log override

## Views

### `resources/views/pages/so/assign.blade.php`

Halaman assign stock per-Keluar. **Stok Tersisa** = stock qty - total assigned lain (Pending/Picked). Coordinator pilih stock + qty, tidak boleh melebihi sisa tersedia.

```
┌─────────────────────────────────────────────────────────┐
│ Assign Stock — Keluar OUT-20260803-0001                 │
├─────────────────────────────────────────────────────────┤
│ SO-001: Product A — Dibutuhkan: 20 unit                 │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ # │ Barcode  │ Rak  │ Qty Ambil │ Stok Tersisa     │ │
│ │ 1 │ STK-001  │ B1   │ [20]      │ 30               │ │
│ │   │ [+ Tambah]                                      │ │
│ └─────────────────────────────────────────────────────┘ │
│ Sisa perlu: 0/20 ✅                                     │
├─────────────────────────────────────────────────────────┤
│ SO-002: Product A — Dibutuhkan: 70 unit                 │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ # │ Barcode  │ Rak  │ Qty Ambil │ Stok Tersisa     │ │
│ │ 1 │ STK-001  │ B1   │ [10]      │ 10 (sisa)        │ │
│ │ 2 │ STK-002  │ B2   │ [25]      │ 25               │ │
│ │ 3 │ STK-003  │ C1   │ [35]      │ 40               │ │
│ │   │ [+ Tambah]                                      │ │
│ └─────────────────────────────────────────────────────┘ │
│ Sisa perlu: 0/70 ✅                                     │
├─────────────────────────────────────────────────────────┤
│ [Simpan Assignments]                                    │
└─────────────────────────────────────────────────────────┘
```

### `resources/views/pages/forklift/pick.blade.php` (update)

Tampilkan assigned stocks bukan FIFO suggestions:

```
┌─────────────────────────────────────────────────────────┐
│ Product A — perlu 20 unit                               │
│                                                         │
│ Instruksi:                                              │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Ambil STK-001 dari Rak B1, 20 unit                  │ │
│ │ [✅ Ambil] [⚠️ Komplain]                           │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Progress: 0/20 unit (0%)                                │
└─────────────────────────────────────────────────────────┘
```

## Override/Komplain Flow

1. Forklift klik "Komplain" → form muncul:
   - Alasan: "Rak B1 kosong" / "Stok rusak" / dll
   - Submit → assignment status → Override
2. Coordinator dapat notifikasi (flash message)
3. Coordinator buka assign page lagi → re-assign dari stok lain

## Testing

1. **Assign stock**: coordinator assign, assignment tersimpan
2. **Forklift pick**: pick sesuai assignment, status → Picked
3. **Override**: forklift komplain, coordinator re-assign
4. **Split stock**: 1 stock untuk 2 SO, qty di-split benar
5. **Available check**: qty assign tidak melebihi stok tersedia
6. **Backward compatible**: tanpa assignment, pick list tetap FIFO
