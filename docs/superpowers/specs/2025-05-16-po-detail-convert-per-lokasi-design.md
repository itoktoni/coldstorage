# PO Detail Convert per Lokasi — Design

## Tujuan

Mengubah alur konversi PO Detail menjadi Masuk di halaman `Convert to Masuk Detail` (`/wms/po-detail/{id}/convert-to-masuk`) sehingga setiap lokasi dikonversi **per baris** menjadi `MasukDetail` sendiri-sendiri, lengkap dengan `MasukRealisasi` yang menyimpan lokasi dan qty (untuk scan rack oleh program forklift nanti).

## Latar Belakang / Konteks

- Alur WMS: PO Detail → konversi → `MasukDetail` → `MasukRealisasi` (berisi `in_realisasi_id_lokasi` + `in_realisasi_qty`). Forklift kemudian men-scan rack untuk merealisasikan perpindahan fisik ke lokasi tsb.
- Saat ini ada 2 tombol:
  1. Tombol **Convert** per-baris → `postConvertSingleRow` (menambah `MasukRealisasi` ke `MasukDetail` existing yang `in_detail_reff`-nya sama).
  2. Tombol **Convert to Masuk** di bawah → `postConvertToMasuk` (loop semua alokasi jadi 1 `MasukDetail` + banyak `MasukRealisasi`).

## Keputusan Desain

1. **Hapus tombol "Convert to Masuk" di bawah** dari UI. Route `postConvertToMasuk` dibiarkan utuh di kode (tidak dihapus) untuk menghindari regresi pada pemanggil lain, namun tidak lagi diakses dari halaman ini.
2. **Tombol Convert per-baris menjadi cara utama.** Tiap klik membuat **`MasukDetail` baru** (bukan menggabung ke yang existing) + **`MasukRealisasi`** untuk baris lokasi tsb.
   - Contoh: PO Detail 1000kg bisa dipecah menjadi beberapa `MasukDetail` (mis. 100kg lalu 900kg) sesuai berapa kali user mengklik convert pada baris-baris lokasi.
3. **`in_detail_qty`** diisi qty dari baris yang dikonversi (bukan total qty PO), agar konsisten dengan qty realisasi baris tsb.

## Perubahan

### Controller `app/Http/Controllers/Wms/PoDetailController.php` — `postConvertSingleRow`

Ubah logika:
- **Sebelum:** reuse `MasukDetail` pertama yang `in_detail_reff == po_detail_code`, tambah `MasukRealisasi` ke situ.
- **Sesudah:** selalu buat `MasukDetail` baru dengan:
  - `in_detail_code` = `MasukDetail::generateCode()`
  - `in_detail_reff` = `$poDetail->po_detail_code`
  - `in_detail_tanggal` = `now()->toDateString()`
  - `in_detail_status` = `MasukStatusEnum::PENDING`
  - `in_detail_id_product` = `$poDetail->po_detail_id_product`
  - `in_detail_qty` = qty baris (`$qty`)
  - `in_detail_catatan` = `'Dikonversi dari PO ' . $poDetail->po->po_code`
- Lalu buat `MasukRealisasi`:
  - `in_realisasi_masuk_code` = code MasukDetail baru
  - `in_realisasi_id_product` = `$poDetail->po_detail_id_product`
  - `in_realisasi_qty` = `$qty`
  - `in_realisasi_id_lokasi` = `$lokasi->lokasi_id`
  - `in_realisasi_barcode` = null
- Validasi tambahan:
  - Pastikan qty tidak membuat total konversi melebihi `po_detail_qty` (periksa sisa qty yang sudah dikonversi untuk PO detail ini).
  - Pertahankan validasi qty ≤ `lokasi_max_qty`.

### View `resources/views/pages/podetail/convert.blade.php`

- Hapus blok `<form ... method="POST">` pembungkus tabel + tombol submit "Convert to Masuk" di bawah (footer). Tabel per-baris tetap, tanpa form wrap di sekelilingnya.
- Pertahankan tombol Convert per-baris (AJAX ke `postConvertSingleRow`).
- Hapus JS validasi total alokasi submit (validasi per-baris yang relevan tetap dipertahankan).
- Header `Alokasi Lokasi` / info tetap.

## Catatan Non-Goals

- Tidak mengubah `postConvertToMasuk` controller (dibiarkan).
- Tidak menambah tabel baru.
- Tidak mengubah tabel `MasukRealisasi` / `MasukDetail` schema.

## Testing

- Buka halaman convert, klik Convert pada satu baris lokasi → muncul 1 `MasukDetail` baru + `MasukRealisasi` dengan lokasi & qty baris tsb.
- Klik lagi pada baris lain → `MasukDetail` baru terpisah.
- Klik pada baris yang sama dua kali → 2 `MasukDetail` terpisah.
- Pastikan qty tidak melebihi sisa qty PO dan kapasitas lokasi.
