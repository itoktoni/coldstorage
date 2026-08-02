# Prepare Sales Order

## Overview

Feature untuk menggabungkan item dari beberapa Sales Order berdasarkan product, sehingga pengambilan stock di gudang lebih efisien.

## Flow

1. User centang beberapa SO di tabel SO
2. Klik button "Prepare"
3. Form review muncul — tabel gabungan: Product | Qty Total | Dari SO
4. User review, submit
5. Sistem buat 1 record `keluar` + N record `keluar_detail` (per product)
6. SO status berubah ke `Prepare`

## Database Changes

### `keluar_detail` — tambah kolom

```php
$table->string('out_detail_reff', 255)->nullable(); // SO codes, comma-separated
```

## Model Changes

### `So` model

- Tambah status `Prepare` ke status options
- Tambah const `STATUS_PREPARE = 'Prepare'`

### `Keluar` model

- Tambah boot method untuk auto-generate `out_code` (format: `OUT-YYYYMMDD-XXXX`)

## Routes

```
GET  /wms/so/prepare  → SoController@getPrepare
POST /wms/so/prepare  → SoController@postPrepare
```

## Controller: SoController

### `getPrepare()`

- Terima `so_ids[]` dari request (array of SO IDs)
- Load SO details, group by `so_detail_id_product`
- Hitung total qty per product
- Kirim data ke view: grouped items, product options, SO list

### `postPrepare()`

- Validate input (details array with product_id, qty, source SOs)
- Buat record `keluar` baru (auto code, tanggal hari ini, status Pending)
- Loop grouped products → buat `keluar_detail` per product
  - `out_detail_reff` = comma-separated SO codes
- Update semua SO status ke `Prepare`
- Redirect ke tabel keluar

## View: `resources/views/pages/so/prepare.blade.php`

Form review dengan:
- Info SO yang dipilih (kode, customer, tanggal)
- Tabel gabungan: Product | Qty | Dari SO
- Qty bisa di-adjust (editabel)
- Button Submit

## SO Table Changes

- Tambah button "Prepare" di action bar (hanya aktif kalau ada checkbox tercentang)
- Tambah JS function `prepareSelected()` — kirim selected SO IDs via GET form

## Stock

Tidak ada pengurangan stock. `keluar_detail` hanya rencana. Stock dikurangi saat realisasi keluar.
