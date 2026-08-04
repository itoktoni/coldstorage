# Forklift Task — Putaway & Pick (Unified Worklist)

Tanggal: 2026-08-03

## Overview

Satu table `forklift_task` menjadi worklist tunggal untuk operator forklift, dipakai untuk **dua arah**:

- **Putaway** (Fase Masuk): naikin pallet dari staging ke rack tujuan.
- **Pick** (Fase Keluar): turunin pallet dari rack ke staging.

Operator forklift hanya **scan** (tidak menekan tombol). Tiap task tercatat siapa yang mengerjakan + timestamp (audit). Granularity task = **per pallet**.

## Problem

1. **Putaway belum punya worklist.** Setelah barang di-scan READY, tidak ada instruksi terstruktur ke forklift: pallet ada di staging mana, naik ke rack mana. Operator tidak tahu lokasi fisik pallet.
2. **Pick belum ada perencanaan supervisor + audit.** Butuh supervisor merencanakan pengambilan pallet (FEFO), dan butuh jejak siapa operator yang mengangkat tiap pallet.
3. **Tidak ada card-locking.** Beberapa operator bisa bekerja paralel; task yang sudah diambil satu operator tidak boleh diambil operator lain.

## Design Decisions

1. **Satu table `forklift_task` untuk putaway + pick.** Dibedakan kolom `ft_type`. Struktur asal→tujuan sama, arah kebalik.
2. **forklift_task standalone** — tidak bergantung pd `stock_assignment`. Pick task hanya soal "pindahkan pallet", bukan "stock untuk SO mana".
3. **Forklift pindah pallet utuh.** Alokasi ke SO terjadi setelah pallet di staging (di halaman so-prepare, warehouse scan barcode). forklift_task tidak menyimpan link ke SO detail.
4. **FEFO** — stock yang paling dekat expired dikeluarkan duluan (bukan FIFO).
5. **Staging area** = row `lokasi` dengan `lokasi_category = 'staging'`. Di-set saat convert-to-masuk, bisa diubah saat realisasi.
6. **Scan strict** — pallet + rack harus sama persis dengan task. Staging tujuan (pick) boleh berbeda dari suggest.
7. **Card-locking** via status: begitu scan pertama, task → Progress + tercatat operator, hilang dari worklist operator lain.

## Alur Lengkap: PO Masuk → Barang Keluar

```
FASE 1 — MASUK (Inbound)
1. PO dibuat
2. PO Detail → Convert to Masuk (pilih rack tujuan + STAGING area) → masuk_detail (Pending)
3. Realisasi scan barcode → masuk_realisasi grouped per pallet (PAL-xxx); staging bisa diubah
4. Qty terpenuhi → status READY → stock STAGING dibuat
   >>> AUTO-CREATE forklift_task (type=putaway) per pallet <<<

FASE 2 — PUTAWAY (naikin ke rack)
5. Forklift buka worklist → task putaway:
   - Asal: Staging A/B/C/D (info)
   - Tujuan: Rack X (scan)
   - Scan pallet → lock (Progress, catat operator)
   - Scan rack tujuan → stock STAGING → IN, task Selesai (hilang)

FASE 3 — KELUAR (Outbound)
6. SO dibuat → RESERVE virtual
7. SO → Prepare → Keluar + KeluarDetail
8. keluar-prepare (supervisor):
   - System alokasi pallet FEFO (expired terdekat duluan)
   - Supervisor override bila perlu → simpan
   >>> AUTO-CREATE forklift_task (type=pick) per pallet <<<
9. Forklift pick:
   - Asal: Rack Y (info)
   - Tujuan: Staging suggest (scan, boleh ganti staging lain)
   - Scan pallet → lock (Progress, catat operator)
   - Scan staging → pallet turun ke staging, task Selesai
10. Warehouse operator bongkar pallet → so-prepare scan barcode → alokasi ke SO
11. SO selesai → RESERVE dihapus
```

Yang dirombak di spec ini: **step 2 (tambah staging), step 4 (auto-create putaway task), step 8 (keluar-prepare + auto-create pick task), step 5 & 9 (worklist scan)**.

## Database Changes

### Table baru: `forklift_task`

```php
Schema::create('forklift_task', function (Blueprint $table) {
    $table->id('forklift_id');
    $table->enum('forklift_type', ['putaway', 'pick']);
    $table->string('forklift_pallet_code', 50);                 // PAL-xxx (grup pallet)
    $table->string('forklift_lokasi_asal', 50)->nullable();     // info: staging (putaway) / rack (pick)
    $table->string('forklift_lokasi_tujuan', 50)->nullable();   // suggest: rack (putaway) / staging (pick)
    $table->string('forklift_lokasi_final', 50)->nullable();    // lokasi tujuan aktual hasil scan
    $table->string('forklift_reff', 100)->nullable();           // in_detail_code (putaway) / out_code (pick)
    $table->enum('forklift_status', ['Pending', 'Progress', 'Done'])->default('Pending');
    $table->string('forklift_operator', 100)->nullable();       // user yang mengerjakan
    $table->timestamp('forklift_scan_asal_at')->nullable();     // saat scan pallet (lock)
    $table->timestamp('forklift_scan_tujuan_at')->nullable();   // saat scan lokasi tujuan (done)
    $table->timestamps();
});
```

Catatan indeks: index `forklift_status`, `forklift_type`, `forklift_pallet_code` untuk query worklist.

### Kolom baru di `masuk_detail`: `in_detail_id_staging`

```php
$table->string('in_detail_id_staging', 50)->nullable()->after('in_detail_id_lokasi');
// FK logis → lokasi.lokasi_code (category = staging)
```

Status `masuk_detail` **wajib** punya `in_detail_id_staging` sebelum bisa → READY. Validasi di `MasukDetailController::postRealisasikan` dan `MasukRealisasiScanner`: jika staging kosong, tolak transisi ke READY dengan pesan "Pilih staging area dulu sebelum set READY".

## Model Changes

### `ForkliftTask` (baru)

```php
class ForkliftTask extends BaseModel
{
    protected $table = 'forklift_task';
    protected $primaryKey = 'forklift_id';

    protected $fillable = [
        'forklift_type', 'forklift_pallet_code', 'forklift_lokasi_asal', 'forklift_lokasi_tujuan',
        'forklift_lokasi_final', 'forklift_reff', 'forklift_status', 'forklift_operator',
        'forklift_scan_asal_at', 'forklift_scan_tujuan_at',
    ];

    protected $casts = [
        'forklift_scan_asal_at'   => 'datetime',
        'forklift_scan_tujuan_at' => 'datetime',
    ];

    const TYPE_PUTAWAY = 'putaway';
    const TYPE_PICK    = 'pick';
    const STATUS_PENDING  = 'Pending';
    const STATUS_PROGRESS = 'Progress';
    const STATUS_DONE     = 'Done';

    public function lokasiAsal()   { return $this->belongsTo(Lokasi::class, 'forklift_lokasi_asal', 'lokasi_code'); }
    public function lokasiTujuan() { return $this->belongsTo(Lokasi::class, 'forklift_lokasi_tujuan', 'lokasi_code'); }
}
```

### `MasukDetail` — tambah relasi staging + fillable

```php
protected $fillable = [ ... , 'in_detail_id_staging'];

public function staging()
{
    return $this->belongsTo(Lokasi::class, 'in_detail_id_staging', 'lokasi_code');
}
```

## Auto-Create Task

### Putaway (saat READY)

Di titik status `masuk_detail` → READY (dua tempat: `MasukDetailController::postRealisasikan` dan `MasukRealisasiScanner` saat qty terpenuhi):

```php
ForkliftTask::firstOrCreate(
    ['forklift_type' => 'putaway', 'forklift_pallet_code' => $groupCode],
    [
        'forklift_lokasi_asal'   => $masukDetail->in_detail_id_staging,   // staging (info)
        'forklift_lokasi_tujuan' => $masukDetail->in_detail_id_lokasi,    // rack tujuan (scan)
        'forklift_reff'          => $masukDetail->in_detail_code,
        'forklift_status'        => 'Pending',
    ]
);
```

### Pick (saat supervisor simpan keluar-prepare)

Supervisor pilih pallet (FEFO auto, bisa override). Untuk tiap pallet terpilih:

```php
ForkliftTask::firstOrCreate(
    ['forklift_type' => 'pick', 'forklift_pallet_code' => $palletCode, 'forklift_reff' => $outCode],
    [
        'forklift_lokasi_asal'   => $rackAsal,       // rack tempat pallet (info)
        'forklift_lokasi_tujuan' => $stagingSuggest, // staging suggest (scan, boleh ganti)
        'forklift_status'        => 'Pending',
    ]
);
```

## Worklist Scan Flow (dua-duanya)

Halaman worklist forklift (scan-only, dark UI, konsisten dgn `pick-scan.blade.php`):

1. Tampilkan task `Pending` + task `Progress` milik operator saat ini.
2. **Scan pallet** (`forklift_pallet_code`):
   - Cari task Pending dengan pallet ini. Tidak ada → tolak "Task tidak ditemukan / sudah dikerjakan".
   - Match → `forklift_status = Progress`, `forklift_operator = auth user`, `forklift_scan_asal_at = now()`. Task lock.
3. **Scan lokasi tujuan**:
   - **Putaway**: harus sama persis `forklift_lokasi_tujuan` (rack). Salah → tolak.
     Sukses → stock (reff=pallet) `STAGING → IN`, `stock_code_lokasi = rack`, `forklift_lokasi_final = rack`, `forklift_status = Done`, `forklift_scan_tujuan_at = now()`.
   - **Pick**: scan staging. Boleh sama atau beda dari `forklift_lokasi_tujuan` (suggest), tapi harus `lokasi_category = staging`. 
     Sukses → stock (reff=pallet) `IN → STAGING`, `stock_code_lokasi = staging discan`, `forklift_lokasi_final = staging`, `forklift_status = Done`, `forklift_scan_tujuan_at = now()`.
4. Task Done → hilang dari worklist.

Aturan scan prefix mengikuti `config/scan.php` (P=pallet, L=lokasi).

## Routes

```
GET  /wms/forklift-task          → ForkliftTaskController@index      (worklist)
POST /wms/forklift-task/scan     → ForkliftTaskController@scan        (proses scan pallet/lokasi)
```

keluar-prepare (sudah ada, diubah generate pick task):
```
GET  /wms/keluar-prepare/{outCode}   → KeluarController@getPrepare
POST /wms/keluar-prepare/{outCode}   → KeluarController@postPrepare  (+ generate forklift_task pick)
```

## Controller Changes

### `ForkliftTaskController` (baru)

- `index()` — worklist: task Pending + Progress milik operator, group by type. Info asal/tujuan + nama lokasi.
- `scan(Request)` — state machine: scan pallet (lock) lalu scan lokasi (done). Auto-detect prefix. Return JSON/flash utk UI scan-only.

### `KeluarController::getPrepare` (ubah)

- Tetap tampilkan kebutuhan item + stock tersedia.
- Alokasi **FEFO**: urutkan stock kandidat by `stock_expired_date` ASC.
- Group stock terpilih **per pallet** (`stock_pallet_code`) untuk preview task.

### `KeluarController::postPrepare` (ubah)

- Simpan alokasi (existing) → untuk tiap pallet terpilih, `firstOrCreate` forklift_task type=pick.

### `MasukDetailController::postRealisasikan` & `MasukRealisasiScanner` (ubah)

- Validasi: tidak boleh READY tanpa `in_detail_id_staging`.
- Saat READY → auto-create forklift_task type=putaway.

### `PoDetailController` convert (ubah)

- Terima input `staging_code` per baris → simpan ke `masuk_detail.in_detail_id_staging`.

## View Changes

### `convert.blade.php` (ubah)

Tiap baris lokasi: tambah **select staging** (opsi dari `lokasi` category=staging) di samping tombol Convert.

### `realisasikan` / scanner (ubah)

Tambah field ganti staging (select category=staging), prefilled dari `in_detail_id_staging`.

### `forklift-task/index.blade.php` (baru)

Worklist scan-only dark UI. Dua seksi: Putaway & Pick. Tiap card: pallet, asal (info), tujuan (suggest), status, operator. Satu input scan besar.

## Testing

1. **Convert set staging** — staging tersimpan di masuk_detail.
2. **READY tanpa staging ditolak** — validasi jalan.
3. **Auto-create putaway** — READY → task putaway Pending kebuat per pallet.
4. **Putaway scan** — scan pallet lock (Progress+operator), scan rack benar → stock IN, Done. Scan rack salah → ditolak.
5. **Card-locking** — task Progress milik A tak muncul di worklist B.
6. **keluar-prepare FEFO** — alokasi urut expired terdekat.
7. **Auto-create pick** — simpan keluar-prepare → task pick Pending per pallet.
8. **Pick scan** — scan pallet lock, scan staging (suggest / staging lain) → stock STAGING, Done. Scan non-staging → ditolak.
9. **Audit** — operator + timestamp asal/tujuan tercatat.
```

