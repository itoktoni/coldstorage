# Dokumentasi Alur Bisnis - Warehouse Management System (WMS)

Dokumen ini menjelaskan alur bisnis operasional pergudangan, mulai dari proses barang masuk (*Inbound*), pengelolaan stok (*Inventory Management*), hingga proses pengeluaran barang (*Outbound*) dengan mekanisme *stok split* (parsial).

---

## 1. Alur Barang Masuk (Inbound Process)

Proses inbound dibagi menjadi dua tahap utama: Perencanaan/Administrasi Dokumen dan Realisasi Fisik di Lapangan.

### Tahap 1: Pencatatan Rencana Masuk (Inbound Detail)
* User membuat dokumen transaksi masuk per produk.
* Data disimpan ke dalam tabel `masuk_detail` dengan atribut:
    * `in_detail_tanggal` (Tanggal rencana masuk)
    * `in_detail_id_product` (ID Produk terkait)
    * `in_detail_reff` (Referensi jika ada, contoh: Nomor PO atau Surat Jalan)
* Status awal diset pada field `in_detail_status` (misal: *Pending* atau *Open*).

### Tahap 2: Realisasi Fisik & Penerimaan Barang
* Saat fisik barang tiba, petugas lapangan melakukan scan barcode per item (Qty 1) atau menginputkan total kuantitas yang diterima.
* Data disimpan ke tabel `masuk_realisasi`:
    * Sistem men-generate kode unik baru pada `in_realisasi_code`.
    * Sistem men-generate **Group Code** (`in_realisasi_group`). Kode group ini menandakan bahwa barang tersebut telah siap (*Ready*), selesai di-wrapping, atau sudah diletakkan di atas pallet tertentu.
* **Update & Sinkronisasi Data:**
    1.  Tabel `masuk_detail` akan memperbarui field `in_detail_qty` dengan total kuantitas akumulasi yang berhasil direalisasikan.
    2.  Data otomatis direferensikan ke tabel `stock` sebagai data **Stok Masuk (Stock In)** baru.
    3.  Pada tabel `stock`, field `stock_type` diisi dengan enum `'IN'`.
    4.  Relasi diikat melalui field referensi group (`stock_reff` / `in_realisasi_group`).

---

## 2. Manajemen Stok (Inventory Management)

* Setiap barang yang berhasil melalui proses realisasi masuk akan memiliki catatan di tabel `stock` dengan lokasi yang ditentukan (`stock_id_lokasi`).
* **Perhitungan Stok Tersedia (Available Stock):**
    > ⚠️ **Aturan Bisnis Penting:** Kuantitas stok yang dianggap tersedia dan valid untuk dialokasikan hanya dihitung dari baris data pada tabel `stock` yang memiliki flag `stock_type = 'IN'`.

---

## 3. Alur Barang Keluar (Outbound Process)

Proses pengeluaran barang mendukung pengambilan penuh maupun pengambilan sebagian (*Partial Picking*) dengan menggunakan mekanisme pemecahan stok (*Split Stock*).

### Tahap 1: Pencatatan Rencana Keluar (Outbound Detail)
* User membuat dokumen pengeluaran pada tabel `keluar` (mencatat `out_tanggal`, `out_code`, `out_reff`, dll).
* User menambahkan detail produk dan jumlah yang diminta pada tabel `keluar_detail` (`out_detail_id_product` dan `out_detail_qty`).

### Tahap 2: Realisasi Pengeluaran & Mekanisme Split Stock
Sistem akan memeriksa `stock_id` yang tersedia. Jika kuantitas yang diminta pada `keluar_detail` lebih kecil daripada kuantitas yang ada pada satu ID Stok tersebut (Pengambilan Parsial), maka sistem akan menjalankan prosedur **Split**:

Misalkan: Stok Lama (`stock_id` 101) memiliki kuantitas **200 pcs**, dan user hanya ingin mengeluarkan **50 pcs**.

1.  **Pengurangan Stok Lama:**
    * Kuantitas pada `stock_id` lama (101) dikurangi dengan jumlah yang keluar: 
        $$\text{Kuantitas Baru} = 200 - 50 = 150 \text{ pcs}$$
    * ID Stok lama (101) tetap bertahan di gudang dengan sisa 150 pcs.
2.  **Pembuatan Stok Baru (Hasil Split):**
    * Sistem otomatis men-generate satu baris data baru di tabel `stock` (misalkan mendapat `stock_id` 102) dengan kuantitas sebesar **50 pcs**.
    * Sistem men-generate barcode baru untuk `stock_id` 102 ini.
    * Field `stock_reff` pada barcode baru (102) akan diisi dengan ID atau nomor dari barcode lama (101) untuk menjelaskan *traceability* (asal-usul barang).
3.  **Pencatatan Riwayat di Tabel Split:**
    * Detail eksekusi pemisahan ini wajib dicatat pada tabel `split`:
        * `split_id_product`: ID Produk terkait.
        * `split_id_stock`: ID dari Stok Lama (`stock_id` 101).
        * `split_id_reff`: ID dari Stok Baru hasil split (`stock_id` 102).
        * `split_qty_old`: Kuantitas sebelum split (200.0).
        * `split_qty_new`: Kuantitas yang diambil / split baru (50.0).
        * `split_qty_waste`: Kuantitas buangan/rusak jika ada (0.0 jika tidak ada).
        * `split_tanggal` & `split_created_at`: Waktu eksekusi.
4.  **Finalisasi Realisasi Keluar:**
    * Barcode baru (`stock_id` 102 berisi 50 pcs) inilah yang diambil secara fisik untuk dikirim.
    * Sistem mencatat transaksi final ini ke tabel `keluar_realisasi` dengan mengikat `out_realisasi_id_stock` ke ID Stok baru yang dibentuk (`stock_id` 102).

---

## 4. Ringkasan Relasi Tabel Terkait

| Nama Tabel | Peran Utama dalam Flow | Field Kunci Alur |
| :--- | :--- | :--- |
| `masuk_detail` | Rencana inbound & akumulasi total qty masuk. | `in_detail_code`, `in_detail_qty`, `in_detail_id_product` |
| `masuk_realisasi` | Validasi fisik, penataan pallet/wrapping (*Grouping*). | `in_realisasi_group`, `in_realisasi_code` |
| `stock` | Lokasi penyimpanan & kalkulasi *available stock* (`stock_type='IN'`). | `stock_id`, `stock_qty`, `stock_reff` |
| `keluar_detail` | Permintaan item & qty outbound. | `out_detail_qty`, `out_detail_id_product` |
| `split` | Audit trail pemecahan stok akibat *partial picking*. | `split_id_stock` (Lama), `split_id_reff` (Baru) |
| `keluar_realisasi` | Eksekusi final pengeluaran barang. | `out_realisasi_id_stock` |