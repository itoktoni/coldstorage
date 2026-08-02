# MasukRealisasi Group Barcode + Forklift Worklist — Design

## Tujuan

1. Saat `MasukDetail` berubah jadi `READY`, sistem otomatis membuat **1 barcode pallet / group barcode** yang mewakili seluruh `MasukRealisasi` dalam grup itu.
2. Barcode pallet disimpan pada `in_realisasi_group` dan bisa dicetak ke PDF.
3. Halaman forklift diubah menjadi **worklist per pallet**: menampilkan kode pallet, product, qty, dan rekomendasi lokasi tujuan.
4. Operator forklift scan barcode pallet, lalu scan QR rack/lokasi tujuan, lalu sistem menyimpan `Stock` IN.

## Konteks

- `MasukRealisasi` sudah menyimpan `in_realisasi_masuk_code`, `in_realisasi_id_product`, `in_realisasi_qty`, `in_realisasi_code_lokasi`, `in_realisasi_barcode`, `in_realisasi_group`.
- `MasukRealisasiScanner` sekarang membuat row realisasi ketika barcode produk di-scan.
- `ForkliftController` dan `resources/views/pages/forklift/index.blade.php` sekarang masih berbasis `MasukDetail`/lokasi tujuan manual.

## Keputusan Desain

1. **Tetap pakai `MasukRealisasi` sebagai sumber worklist.**
   - Tidak bikin tabel queue baru.
   - Worklist forklift diambil dari query `MasukRealisasi` yang sudah punya `in_realisasi_group`.
2. **`in_realisasi_group` = barcode pallet.**
   - 1 grup pallet mewakili 1 set realisasi yang sudah selesai QC.
   - Barcode ini unik dan dipakai sebagai ID pallet saat forklift bekerja.
3. **Trigger auto saat `MasukDetail` menjadi `READY`.**
   - Jika semua realisasi untuk `MasukDetail` sudah masuk, sistem generate 1 kode pallet dan mengisi `in_realisasi_group` untuk seluruh row dalam grup tsb.
4. **Workflow forklift 2 langkah.**
   - Langkah 1: scan / pilih pallet dari worklist.
   - Langkah 2: scan rack QR lokasi tujuan (`lokasi_code`).
   - Setelah valid, sistem membuat `Stock` status `IN`, lalu menandai proses selesai.

## Perubahan

### 1. Trigger group barcode

Di `MasukRealisasiScanner`:
- Saat total realisasi mencapai `in_detail_qty` dan status akan menjadi `READY`.
- Generate `group barcode` unik untuk grup itu.
- Update semua `MasukRealisasi` milik `in_realisasi_masuk_code` tsb:
  - `in_realisasi_group = <barcode pallet>`
- Barcode pallet dibuat sekali saja per `MasukDetail`.

### 2. PDF barcode pallet

Buat endpoint untuk print barcode pallet:
- Input: `in_realisasi_group`
- Output: PDF 1 halaman berisi:
  - barcode pallet
  - kode pallet
  - product utama / ringkasan grup
  - qty total
  - optional catatan / referensi `MasukDetail`

### 3. Forklift worklist

`ForkliftController` diubah jadi query grup pallet:
- Group by `in_realisasi_group`
- Tampilkan per pallet:
  - kode pallet (`in_realisasi_group`)
  - product
  - total qty
  - rekomendasi lokasi tujuan
  - status (ready to move / already moved)
- Item worklist menampilkan tombol `Scan Pallet`.

### 4. Scan pallet + scan rack

Halaman forklift baru punya 2 input flow:
- Input barcode pallet
- Input scan rack/lokasi tujuan

Validasi:
- pallet ada dan belum selesai
- lokasi sesuai kategori product
- lokasi punya kapasitas cukup
- pallet belum pernah dipindahkan ke lokasi tsb

Jika valid:
- buat `Stock` IN
- update status proses grup selesai
- tandai pallet sebagai selesai / moved

### 5. View forklift

`resources/views/pages/forklift/index.blade.php` diubah:
- dari kartu per `MasukDetail`
- menjadi kartu per `in_realisasi_group`
- tampil ringkas dan operasional, fokus ke scanning
- gunakan card-based layout, no responsive grid di level utama

## Data Flow

1. `MasukRealisasiScanner` menambah row realisasi.
2. Saat total terpenuhi, status `READY`.
3. Sistem generate `in_realisasi_group` barcode pallet.
4. Operator print PDF pallet barcode.
5. Forklift halaman menampilkan list pallet siap pindah.
6. Operator scan barcode pallet.
7. Operator scan rack QR lokasi.
8. Sistem simpan `Stock` IN dan close work item.

## Error Handling

- Barcode pallet kosong / tidak valid → tolak.
- Pallet sudah selesai → tolak.
- Lokasi tidak sesuai kategori → tolak.
- Lokasi penuh → tolak.
- Pallet belum `READY` → tidak muncul di worklist.

## Testing

- Scan produk sampai `MasukDetail` jadi `READY`.
- Pastikan `in_realisasi_group` terisi di semua row grup.
- PDF pallet barcode bisa di-download.
- Forklift list menampilkan grup pallet, bukan row mentah.
- Scan pallet + scan rack valid → `Stock` tercipta.
- Scan lokasi salah / penuh → error.
