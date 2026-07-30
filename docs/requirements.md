# Dokumen Requirements - Warehouse Management System (WMS)

## Pendahuluan

Warehouse Management System (WMS) adalah sistem yang dirancang untuk mengelola seluruh operasional pergudangan secara terintegrasi. Sistem ini mencakup tiga alur utama:

1. **Proses Barang Masuk (Inbound)** — dari pencatatan rencana penerimaan hingga realisasi fisik dan penempatan stok di lokasi gudang.
2. **Manajemen Stok (Inventory)** — pengelolaan data stok secara real-time berdasarkan lokasi, produk, dan status stok.
3. **Proses Barang Keluar (Outbound)** — dari pembuatan order pengeluaran hingga realisasi pengambilan, termasuk mekanisme *split stock* untuk pengambilan parsial.

Sistem dibangun di atas struktur database relasional yang terdiri dari tabel: `gudang`, `lokasi`, `product`, `stock`, `masuk_detail`, `masuk_realisasi`, `keluar`, `keluar_detail`, `keluar_realisasi`, dan `split`.

---

## Requirements

### Requirement 1 — Manajemen Master Data Gudang & Lokasi

**User Story:** Sebagai admin gudang, saya ingin mengelola data gudang dan lokasi penyimpanan, agar setiap stok dapat ditempatkan dan dilacak pada posisi fisik yang akurat.

#### Acceptance Criteria

1. WHEN admin membuat data gudang baru THEN sistem SHALL menyimpan data ke tabel `gudang` dengan field `gudang_id` (auto-increment) dan `gudang_nama`.
2. WHEN admin membuat data lokasi baru THEN sistem SHALL menyimpan data ke tabel `lokasi` dengan field `lokasi_id`, `lokasi_nama`, dan `lokasi_id_gudang` yang mereferensikan `gudang`.
3. WHEN admin menyimpan lokasi tanpa memilih gudang THEN sistem SHALL menolak penyimpanan dan menampilkan pesan validasi bahwa gudang wajib dipilih.
4. WHEN admin melihat daftar lokasi THEN sistem SHALL menampilkan nama gudang yang berelasi dengan setiap lokasi.
5. WHEN admin menghapus gudang yang masih memiliki lokasi aktif THEN sistem SHALL menolak penghapusan dan menampilkan pesan bahwa gudang masih digunakan.

---

### Requirement 2 — Manajemen Master Data Produk

**User Story:** Sebagai admin, saya ingin mengelola data produk, agar setiap transaksi inbound dan outbound dapat dikaitkan ke produk yang tepat.

#### Acceptance Criteria

1. WHEN admin membuat produk baru THEN sistem SHALL menyimpan data ke tabel `product` dengan field `product_id` (auto-increment), `product_nama`, dan `product_harga`.
2. WHEN admin mengosongkan field `product_nama` saat menyimpan THEN sistem SHALL menolak penyimpanan dan menampilkan pesan validasi.
3. WHEN admin mengubah `product_harga` THEN sistem SHALL memperbarui nilai harga tanpa mempengaruhi data stok yang sudah ada.
4. WHEN admin melihat daftar produk THEN sistem SHALL menampilkan semua produk beserta informasi harga terkini.

---

### Requirement 3 — Pencatatan Rencana Barang Masuk (Inbound Planning)

**User Story:** Sebagai staff administrasi, saya ingin membuat dokumen rencana penerimaan barang, agar tim lapangan mengetahui barang apa saja yang akan datang beserta referensi dokumennya.

#### Acceptance Criteria

1. WHEN staff membuat rencana masuk baru THEN sistem SHALL menyimpan data ke tabel `masuk_detail` dengan field: `in_detail_code` (unique, generated otomatis), `in_detail_reff`, `in_detail_tanggal`, `in_detail_id_product`, `in_detail_status`, `in_detail_catatan`, `in_detail_created_at`, dan `in_detail_created_by`.
2. WHEN staff menyimpan rencana masuk THEN sistem SHALL mengisi `in_detail_status` dengan nilai default `'Pending'`.
3. WHEN sistem men-generate `in_detail_code` THEN sistem SHALL memastikan nilai tersebut unik di seluruh tabel `masuk_detail`.
4. WHEN staff memilih produk pada rencana masuk THEN sistem SHALL memvalidasi bahwa `in_detail_id_product` mereferensikan data valid di tabel `product`.
5. WHEN staff melihat daftar rencana masuk THEN sistem SHALL menampilkan nama produk, tanggal, status, dan nomor referensi dari setiap dokumen.
6. IF `in_detail_status` adalah `'Pending'` THEN sistem SHALL mengizinkan perubahan data pada dokumen tersebut.
7. IF `in_detail_status` bukan `'Pending'` THEN sistem SHALL memblokir perubahan data dan menampilkan pesan bahwa dokumen sudah diproses.

---

### Requirement 4 — Realisasi Fisik Barang Masuk (Inbound Receiving)

**User Story:** Sebagai petugas lapangan, saya ingin mencatat penerimaan fisik barang beserta pengelompokan pallet-nya, agar setiap item yang masuk terdokumentasi dan stok otomatis terupdate.

#### Acceptance Criteria

1. WHEN petugas melakukan realisasi penerimaan THEN sistem SHALL menyimpan data ke tabel `masuk_realisasi` dengan field: `in_realisasi_id`, `in_realisasi_masuk_code` (FK ke `masuk_detail.in_detail_code`), `in_realisasi_code` (generated unik), `in_realisasi_id_product`, `in_realisasi_qty`, dan `in_realisasi_group`.
2. WHEN sistem men-generate `in_realisasi_code` THEN sistem SHALL memastikan nilai tersebut unik untuk setiap item yang direalisasikan.
3. WHEN petugas menyelesaikan satu sesi realisasi THEN sistem SHALL men-generate `in_realisasi_group` yang sama untuk semua item dalam satu kelompok pallet/wrapping.
4. WHEN realisasi berhasil disimpan THEN sistem SHALL secara otomatis membuat satu baris baru di tabel `stock` dengan:
   - `stock_id_product` diisi dari `in_realisasi_id_product`
   - `stock_qty` diisi dari `in_realisasi_qty`
   - `stock_reff` diisi dari `in_realisasi_group`
   - `stock_type` diisi dengan nilai `'IN'`
   - `stock_code` di-generate sebagai barcode unik
5. WHEN realisasi berhasil disimpan THEN sistem SHALL mengakumulasi nilai `in_detail_qty` pada `masuk_detail` yang bersangkutan dengan total qty yang direalisasikan.
6. WHEN realisasi berhasil disimpan THEN sistem SHALL memperbarui `in_detail_status` pada `masuk_detail` menjadi `'In Progress'` jika sebelumnya `'Pending'`.
7. IF seluruh qty pada `masuk_detail` sudah terealisasi THEN sistem SHALL memperbarui `in_detail_status` menjadi `'Done'`.
8. WHEN petugas memasukkan qty realisasi lebih dari 0 THEN sistem SHALL menerima input tersebut dan memvalidasi nilainya adalah bilangan bulat positif.

---

### Requirement 5 — Manajemen Stok (Inventory)

**User Story:** Sebagai manajer gudang, saya ingin melihat dan mengelola stok yang tersedia per produk dan per lokasi, agar pengambilan keputusan terkait penempatan dan alokasi barang dapat dilakukan dengan akurat.

#### Acceptance Criteria

1. WHEN sistem menghitung stok tersedia THEN sistem SHALL hanya memperhitungkan baris pada tabel `stock` yang memiliki `stock_type = 'IN'`.
2. WHEN manajer melihat detail stok THEN sistem SHALL menampilkan: `stock_id`, `stock_code`, nama produk, nama lokasi, nama gudang, `stock_qty`, `stock_expired_date`, dan `stock_reff`.
3. WHEN stok ditambahkan melalui proses inbound THEN sistem SHALL mengisi `stock_id_lokasi` sesuai lokasi yang ditentukan saat realisasi.
4. WHEN stok dialokasikan untuk outbound (proses split) THEN sistem SHALL mengubah `stock_type` menjadi `'OUT'` pada baris stok yang diambil sepenuhnya.
5. WHEN manajer mencari stok berdasarkan produk THEN sistem SHALL menampilkan semua baris stok dengan `stock_type = 'IN'` untuk produk tersebut.
6. WHEN manajer mencari stok berdasarkan lokasi THEN sistem SHALL menampilkan semua baris stok aktif (`stock_type = 'IN'`) di lokasi tersebut.
7. IF `stock_expired_date` telah melewati tanggal hari ini THEN sistem SHALL menandai stok tersebut sebagai *Expired* pada tampilan.

---

### Requirement 6 — Pencatatan Rencana Barang Keluar (Outbound Planning)

**User Story:** Sebagai staff administrasi, saya ingin membuat dokumen order pengeluaran barang lengkap dengan detail produk dan kuantitas yang diminta, agar proses picking di lapangan memiliki acuan yang jelas.

#### Acceptance Criteria

1. WHEN staff membuat dokumen keluar baru THEN sistem SHALL menyimpan data ke tabel `keluar` dengan field: `out_code` (unique, generated otomatis), `out_reff`, `out_tanggal`, `out_status`, `out_catatan`, `out_created_at`, dan `out_created_by`.
2. WHEN staff menyimpan dokumen keluar THEN sistem SHALL mengisi `out_status` dengan nilai default `'Pending'`.
3. WHEN staff menambahkan item ke dokumen keluar THEN sistem SHALL menyimpan ke tabel `keluar_detail` dengan field: `out_detail_id`, `out_detail_code_keluar` (FK ke `keluar.out_code`), `out_detail_id_product`, `out_detail_code`, dan `out_detail_qty`.
4. WHEN staff memasukkan `out_detail_qty` THEN sistem SHALL memvalidasi bahwa nilainya adalah bilangan bulat positif lebih dari 0.
5. WHEN staff memilih produk pada `keluar_detail` THEN sistem SHALL memvalidasi bahwa `out_detail_id_product` mereferensikan data valid di tabel `product`.
6. WHEN staff melihat daftar dokumen keluar THEN sistem SHALL menampilkan: `out_code`, `out_reff`, `out_tanggal`, `out_status`, dan total item.
7. IF `out_status` adalah `'Pending'` THEN sistem SHALL mengizinkan penambahan atau penghapusan item detail.
8. IF `out_status` bukan `'Pending'` THEN sistem SHALL memblokir perubahan dan menampilkan pesan bahwa dokumen sedang atau sudah diproses.

---

### Requirement 7 — Realisasi Pengeluaran & Split Stock (Outbound Execution)

**User Story:** Sebagai petugas lapangan, saya ingin mengeksekusi pengeluaran barang berdasarkan dokumen order, termasuk mekanisme pemecahan stok jika kuantitas yang diambil lebih kecil dari satu unit stok, agar pengambilan parsial dapat dilakukan tanpa kehilangan akurasi data.

#### Acceptance Criteria

1. WHEN petugas memulai realisasi keluar THEN sistem SHALL memeriksa ketersediaan stok (`stock_type = 'IN'`) untuk produk yang diminta.
2. IF stok tersedia tidak mencukupi untuk memenuhi `out_detail_qty` THEN sistem SHALL menampilkan pesan peringatan dan tidak mengizinkan proses realisasi berlanjut.
3. WHEN kuantitas yang diminta (`out_detail_qty`) sama dengan atau lebih besar dari `stock_qty` pada stok yang dipilih (Full Picking) THEN sistem SHALL langsung mengubah `stock_type` pada stok tersebut menjadi `'OUT'` tanpa membuat baris stok baru.
4. WHEN kuantitas yang diminta (`out_detail_qty`) lebih kecil dari `stock_qty` pada stok yang dipilih (Partial Picking) THEN sistem SHALL menjalankan prosedur Split Stock sebagai berikut:
   - a. Mengurangi `stock_qty` pada stok lama sebesar jumlah yang diambil.
   - b. Membuat satu baris baru di tabel `stock` dengan `stock_qty` sebesar jumlah yang diambil, `stock_type = 'IN'` sementara, `stock_reff` diisi dengan `stock_code` dari stok lama (traceability), dan `stock_code` baru di-generate.
   - c. Mencatat riwayat split ke tabel `split` dengan field: `split_id_product`, `split_id_stock` (stok lama), `split_id_reff` (stok baru), `split_qty_old`, `split_qty_new`, `split_qty_waste`, `split_tanggal`, `split_created_by`, dan `split_created_at`.
5. WHEN split stock berhasil THEN sistem SHALL mengubah `stock_type` pada stok baru (hasil split) menjadi `'OUT'`.
6. WHEN realisasi keluar berhasil THEN sistem SHALL menyimpan data ke tabel `keluar_realisasi` dengan field: `out_realisasi_id`, `out_realisasi_id_detail` (FK ke `keluar_detail.out_detail_id`), `out_realisasi_code`, dan `out_realisasi_id_stock` (mereferensikan stok yang diambil — stok baru jika ada split).
7. WHEN seluruh item pada `keluar_detail` sudah terealisasi THEN sistem SHALL memperbarui `out_status` pada tabel `keluar` menjadi `'Done'`.
8. WHEN realisasi keluar sebagian selesai THEN sistem SHALL memperbarui `out_status` menjadi `'In Progress'`.
9. WHEN petugas mencatat `split_qty_waste` THEN sistem SHALL menerima nilai nol (0.0) atau positif sebagai kuantitas barang rusak/terbuang selama proses split.

---

### Requirement 8 — Traceability & Audit Trail Split Stock

**User Story:** Sebagai manajer gudang, saya ingin melihat riwayat lengkap setiap pemecahan stok yang terjadi, agar asal-usul setiap unit stok dapat ditelusuri secara akurat.

#### Acceptance Criteria

1. WHEN manajer melihat riwayat split THEN sistem SHALL menampilkan data dari tabel `split` meliputi: produk, stok lama (`split_id_stock`), stok baru (`split_id_reff`), qty lama, qty baru, qty waste, tanggal, dan operator yang melakukan.
2. WHEN manajer menelusuri asal stok tertentu THEN sistem SHALL dapat menampilkan rantai split dari `stock_reff` secara hierarki (stok asal → hasil split 1 → hasil split 2, dst.).
3. WHEN stok baru hasil split dibuat THEN sistem SHALL mengisi `stock_reff` pada stok baru dengan `stock_code` dari stok lama.
4. WHEN manajer melihat detail stok THEN sistem SHALL menampilkan informasi apakah stok tersebut merupakan hasil split beserta referensi stok asalnya.

---

### Requirement 9 — Validasi Bisnis & Konsistensi Data

**User Story:** Sebagai sistem, saya ingin memastikan semua transaksi mengikuti aturan bisnis yang berlaku, agar integritas data warehouse selalu terjaga.

#### Acceptance Criteria

1. WHEN ada transaksi yang akan membuat `stock_qty` menjadi negatif THEN sistem SHALL menolak transaksi tersebut dan menampilkan pesan error yang informatif.
2. WHEN proses split dieksekusi THEN sistem SHALL memvalidasi bahwa `split_qty_new + split_qty_waste <= split_qty_old`.
3. WHEN status dokumen (`masuk_detail` atau `keluar`) adalah `'Done'` THEN sistem SHALL memblokir semua perubahan pada dokumen tersebut.
4. WHEN data direferensikan antar tabel melalui foreign key THEN sistem SHALL memastikan integritas referensial terjaga (tidak dapat menghapus data yang masih direferensikan).
5. WHEN petugas mencoba mengalokasikan stok yang `stock_type = 'OUT'` THEN sistem SHALL menolak alokasi dan menampilkan pesan bahwa stok sudah tidak tersedia.

---

### Requirement 10 — Laporan & Ringkasan Stok

**User Story:** Sebagai manajer gudang, saya ingin melihat laporan ringkasan stok dan histori transaksi, agar kondisi gudang dapat dimonitor secara menyeluruh.

#### Acceptance Criteria

1. WHEN manajer membuka halaman ringkasan stok THEN sistem SHALL menampilkan total stok tersedia per produk berdasarkan SUM(`stock_qty`) dari baris dengan `stock_type = 'IN'`.
2. WHEN manajer melihat laporan inbound THEN sistem SHALL menampilkan daftar `masuk_detail` beserta status realisasinya dan total qty yang sudah masuk.
3. WHEN manajer melihat laporan outbound THEN sistem SHALL menampilkan daftar `keluar` beserta status dan detail item yang sudah direalisasikan.
4. WHEN manajer memfilter laporan berdasarkan rentang tanggal THEN sistem SHALL menampilkan hanya transaksi dalam rentang tanggal yang dipilih.
5. WHEN manajer memfilter laporan berdasarkan gudang atau lokasi THEN sistem SHALL menampilkan stok dan transaksi yang terkait dengan gudang atau lokasi tersebut.

---

### Requirement 11 — Stock Opname (Random & Terjadwal)

**User Story:** Sebagai manajer gudang, saya ingin melakukan penghitungan stok fisik secara random maupun terjadwal, agar perbedaan antara data sistem dan kondisi fisik gudang dapat dideteksi dan dikoreksi tepat waktu.

#### Acceptance Criteria

1. WHEN manajer membuat sesi opname baru THEN sistem SHALL mendukung dua tipe opname: `'Random'` (dilakukan kapan saja tanpa jadwal tetap) dan `'Scheduled'` (berdasarkan jadwal yang dikonfigurasi).
2. WHEN manajer mengonfigurasi opname terjadwal THEN sistem SHALL menyimpan jadwal berulang (harian, mingguan, bulanan) dan secara otomatis membuat sesi opname baru sesuai jadwal tersebut.
3. WHEN sesi opname dimulai THEN sistem SHALL mengambil snapshot data stok aktif (`stock_type = 'IN'`) sebagai acuan (sistem quantity) untuk periode opname tersebut.
4. WHEN petugas menginput hasil hitung fisik THEN sistem SHALL menyimpan `qty_fisik` per item stok dan membandingkannya dengan `qty_sistem` dari snapshot.
5. WHEN petugas selesai menginput seluruh item THEN sistem SHALL menghitung selisih (`qty_fisik - qty_sistem`) untuk setiap item dan menampilkan ringkasan perbedaan.
6. IF terdapat selisih antara qty fisik dan qty sistem THEN sistem SHALL menandai item tersebut sebagai *Discrepancy* dan meminta konfirmasi penyesuaian dari manajer.
7. WHEN manajer menyetujui penyesuaian stok THEN sistem SHALL memperbarui `stock_qty` pada tabel `stock` sesuai hasil hitung fisik dan mencatat riwayat koreksi beserta alasannya.
8. WHEN manajer menolak penyesuaian THEN sistem SHALL mempertahankan data stok lama dan menandai item sebagai *Pending Review*.
9. WHEN opname berlangsung THEN sistem SHALL memblokir transaksi outbound pada item stok yang sedang dalam proses opname untuk mencegah data tidak konsisten.
10. WHEN sesi opname selesai THEN sistem SHALL menghasilkan laporan opname yang memuat: tanggal, tipe opname, daftar item dengan qty sistem, qty fisik, selisih, dan status penyesuaian.
11. WHEN manajer melihat riwayat opname THEN sistem SHALL menampilkan semua sesi opname sebelumnya beserta ringkasan temuan dan tindakan koreksi yang dilakukan.
