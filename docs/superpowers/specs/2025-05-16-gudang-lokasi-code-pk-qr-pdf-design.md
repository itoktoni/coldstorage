# Gudang & Lokasi Code PK + QR Code PDF — Design

## Tujuan

1. Mengganti PK auto-increment `gudang_id` dan `lokasi_id` menjadi **string code** (`gudang_code` & `lokasi_code`) sebagai PK baru.
2. Semua FK yang merujuk ke kolom-kolom tsb ikut rename: `lokasi_id_gudang` → `code_gudang` (lokal nama: `lokasi_code_gudang`), `stock_id_lokasi` → `stock_code_lokasi`, `in_realisasi_id_lokasi` → `in_realisasi_code_lokasi`.
3. Membuat fitur **Print QR Code per lokasi** yang menghasilkan 1 halaman PDF berisi QR code lokasi (encode `lokasi_code`) + info lokasi.

## Latar Belakang / Konteks

- `gudang_id` & `lokasi_id`: bigint auto-increment PK.
- `lokasi_code`/`gudang_code`: string unik yang bisa diisi user (`R1S2` = rack 1 stack 2).
- Code akan jadi PK supaya bisa di-encode langsung ke QR tanpa lookup tambahan.
- Forklift program scan QR → identifikasi lokasi via code.

## Keputusan Desain

1. **PK tipe**: `string`, `$incrementing = false`, `$keyType = 'string'`.
2. **Format kertas**: tidak fixed ke label thermal; gunakan ukuran **A4** agar muat 1 lokasi per halaman (lebih jelas saat preview/cetak).
3. **Library QR**: `milon/barcode` (`DNS2D::getBarcodePNG`) sudah support QR 2D — tidak perlu tambah package.
4. **Library PDF**: `barryvdh/laravel-dompdf` sudah ada.
5. **Migrasi baru** untuk rename PK/FK tipe data, tidak edit migrasi lama (best practice Laravel).

## Perubahan

### Migrasi baru

**`rename_*_to_code_pk.php`** — satu migrasi terstruktur:
- `gudang`: drop PK & auto-inc pada `gudang_id` (jika migrasi lama tipe `id('gudang_id')` ⇒ bigint auto-inc). Rename kolom → `gudang_code` (string unique), set jadi PK baru. Migrasikan data: untuk row yang sudah ada, set `gudang_code` = `gudang_id` (cast string) atau auto-generate `GD-{id}` jika ada.
- `lokasi`: sama — rename ke `lokasi_code` (string PK).
- `lokasi.lokasi_id_gudang` → rename ke `lokasi_code_gudang` (string FK ke `gudang.gudang_code`).
- `stock.stock_id_lokasi` → rename ke `stock_code_lokasi` (string FK).
- `masuk_realisasi.in_realisasi_id_lokasi` → rename ke `in_realisasi_code_lokasi` (string FK).

Urutan langkah migrasi di `up()`:
1. Tambah kolom baru string nullable + unique di tabel yang punya PK (`gudang_code`, `lokasi_code`).
2. Isi dari kolom ID lama (cast string).
3. Drop FK di tabel referensi (`lokasi.lokasi_id_gudang`, `stock.stock_id_lokasi`, `masuk_realisasi.in_realisasi_id_lokasi`).
4. Rename FK ke nama baru (`lokasi_code_gudang`, dll).
5. Ubah tipe jadi string dan isi (cast dari FK lama).
6. Tambah FK baru + set PK baru.
7. Drop kolom ID lama.

Untuk data existing: gunakan raw SQL `DB::statement` karena Schema builder tidak nyaman untuk semua langkah tipe PK change.

### Model

- `Gudang`: `$primaryKey = 'gudang_code'`, `$keyType = 'string'`, `$incrementing = false`. `fillable` tambah `gudang_code`. Update relasi ke `Lokasi` (`lokasi_code_gudang`).
- `Lokasi`: `$primaryKey = 'lokasi_code'`, `$keyType = 'string'`, `$incrementing = false`. `fillable` tambah `lokasi_code`. Update relasi & `rules()` (FK jadi `lokasi_code_gudang` + `lokasi_code` wajib unique).
- `Stock`: update FK ke `stock_code_lokasi`.
- `MasukRealisasi`: update FK & relasi ke `in_realisasi_code_lokasi`.

### Controller

- `LokasiController::share()` — `gudangOptions` jadi `pluck('gudang_nama','gudang_code')`.
- `LokasiController::printQrPdf(string $code)` (BARU) — cari lokasi by `lokasi_code`, generate QR via `DNS2D::getBarcodePNG($lokasi->lokasi_code, 'QRCODE', 8, 8)`, render blade PDF, return `Pdf::download("qr-lokasi-{$code}.pdf")`.
- `MasukDetailController`, `MasukRealisasiController`, `StockController` — `lokasiOptions` jadi `pluck('lokasi_nama','lokasi_code')`; validasi & insert pakai `lokasi_code`.
- `PoDetailController` — array `lokasi_id` jadi `lokasi_code` di seluruh method; insert `MasukRealisasi` pakai `in_realisasi_code_lokasi`.
- `ForkliftController` — request param `lokasi_id` jadi `lokasi_code`; insert pakai field baru.

### Routes (web.php)

- `Route::get('/wms/lokasi/{code}/print-qr', [LokasiController::class, 'printQrPdf'])->name('wms-lokasi.printQr');`

### Views

- `pages/lokasi/form.blade.php`: tambah input `lokasi_code` (string required, unique).
- `pages/gudang/form.blade.php`: tambah input `gudang_code` jika belum ada.
- `pages/lokasi/table.blade.php`: tambah action "Print QR" → `route('wms-lokasi.printQr', ['code' => $table->field_primary])`.
- `pages/forklift/index.blade.php`, `pages/stock/form.blade.php`, `pages/masukrealisasi/form.blade.php`, `pages/podetail/convert.blade.php`: ganti `lokasi_id` → `lokasi_code` di name/value.

### View PDF

- `resources/views/pdf/lokasi-qr.blade.php`: layout A4 portrait, center, QR code besar (≈ 40% halaman), di bawah QR tampilkan `lokasi_code` (besar), `lokasi_nama`, nama gudang.

## Catatan / Risiko

- Jika ada row dengan PK duplikat saat migrasi (kemungkinan kecil karena auto-inc), migrasi gagal → user harus backup dulu.
- Setelah rename, semua route param model binding `Route::auto` akan resolve `lokasi_id` → tetap resolve via `field_primary`, jadi aman.
- Tabel lain yang punya `lokasi_id_*` lain (mis. `in_detail_id_lokasi` di `MasukDetail` — ada di Model line 51, tapi tidak ada di migrations saat ini — kemungkinan dead code): tetap saya rename jika memang ada kolom di DB. Saya skip jika tidak ada kolom fisiknya.

## Testing

- `php artisan migrate` tanpa error.
- Buka halaman lokasi → klik "Print QR" → PDF terdownload dengan QR + info.
- Scan QR dengan HP → muncul string `lokasi_code`.
- Convert PO Detail → klik Convert per lokasi → `MasukRealisasi.in_realisasi_code_lokasi` terisi.
- Forklift scan → lokasi match.
