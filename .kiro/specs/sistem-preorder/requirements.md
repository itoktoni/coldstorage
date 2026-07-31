# Requirements Document

## Introduction

Sistem pre-order untuk kebutuhan pokok (beras, ikan, telur, dll) yang dapat diakses melalui link yang dibagikan via WhatsApp atau Instagram. Pembeli membuka link, melihat katalog produk, menambahkan barang ke keranjang, memilih jadwal COD (tempat, hari, dan jam pengambilan), lalu membayar via QRIS. Setelah pembayaran berhasil, pembeli akan menerima notifikasi konfirmasi melalui WhatsApp. Admin dapat mengelola produk flash sale, kuota, slot COD, dan memantau pesanan.

## Requirements

### Requirement 1 — Katalog Produk & Flash Sales

**User Story:** Sebagai pembeli, saya ingin melihat katalog produk yang sedang dijual beserta kuota tersisa, sehingga saya bisa memilih dan memesan produk yang saya butuhkan.

#### Acceptance Criteria

1. WHEN pembeli membuka link katalog THEN sistem SHALL menampilkan daftar produk aktif beserta nama, foto, harga, stok tersisa, dan batas maksimal pembelian per orang.
2. WHEN kuota produk habis THEN sistem SHALL menampilkan status "Habis" dan menonaktifkan tombol tambah ke keranjang untuk produk tersebut.
3. WHEN flash sale belum dimulai atau sudah berakhir THEN sistem SHALL menampilkan countdown timer atau status yang sesuai.
4. IF produk memiliki batas maksimal pembelian (misal 4 item) THEN sistem SHALL membatasi input quantity sesuai batas tersebut.
5. WHEN halaman katalog dibuka dari link WhatsApp atau Instagram THEN sistem SHALL memuat halaman dengan cepat dan responsif di perangkat mobile.

### Requirement 2 — Keranjang Belanja (Cart)

**User Story:** Sebagai pembeli, saya ingin menambahkan produk ke keranjang dan mengaturnya sebelum checkout, sehingga saya bisa memesan beberapa produk sekaligus.

#### Acceptance Criteria

1. WHEN pembeli menekan tombol "Tambah ke Keranjang" pada suatu produk THEN sistem SHALL menambahkan produk tersebut ke keranjang dan memperbarui jumlah item di ikon keranjang.
2. WHEN pembeli membuka keranjang THEN sistem SHALL menampilkan daftar produk yang dipilih, jumlah, harga satuan, dan total harga keseluruhan.
3. WHEN pembeli mengubah jumlah item di keranjang THEN sistem SHALL memperbarui total harga secara real-time.
4. WHEN pembeli menghapus item dari keranjang THEN sistem SHALL memperbarui keranjang dan total harga.
5. IF jumlah item di keranjang melebihi batas maksimal per produk THEN sistem SHALL menampilkan pesan error dan tidak mengizinkan penambahan.

### Requirement 3 — Form Pemesanan & Data Pembeli

**User Story:** Sebagai pembeli, saya ingin mengisi data diri yang diperlukan untuk pemesanan, sehingga admin tahu siapa yang memesan dan dapat menghubungi saya jika diperlukan.

#### Acceptance Criteria

1. WHEN pembeli melanjutkan ke checkout dari keranjang THEN sistem SHALL menampilkan form dengan field: nama lengkap dan nomor WhatsApp.
2. WHEN pembeli mengisi nomor WhatsApp THEN sistem SHALL memvalidasi format nomor (minimal 10 digit, dimulai dengan 08 atau +62).
3. WHEN form disubmit THEN sistem SHALL memvalidasi semua field wajib sebelum melanjutkan ke pemilihan jadwal COD.
4. WHEN validasi berhasil THEN sistem SHALL mengunci stok sementara (reservation) selama 10 menit untuk memberi waktu pembayaran.

### Requirement 4 — Metode Pengiriman

**User Story:** Sebagai pembeli, saya ingin memilih antara ambil sendiri di lokasi COD atau dikirim ke rumah, sehingga saya bisa memilih cara yang paling nyaman.

#### Acceptance Criteria

1. WHEN pembeli melanjutkan ke pemilihan pengiriman THEN sistem SHALL menampilkan dua opsi: "Ambil di Lokasi COD" dan "Antar ke Rumah".
2. WHEN pembeli memilih "Ambil di Lokasi COD" THEN sistem SHALL menampilkan daftar lokasi COD yang tersedia beserta hari dan jam (contoh: Pasar Potorono — Senin, 13.00–14.00).
3. WHEN kapasitas slot COD penuh THEN sistem SHALL menonaktifkan slot tersebut dan tidak mengizinkan pemilihan.
4. WHEN pembeli memilih slot COD THEN sistem SHALL menampilkan konfirmasi ringkasan pesanan (produk, jumlah, total, lokasi, dan jadwal COD) sebelum pembayaran.
5. WHEN pembeli memilih "Antar ke Rumah" THEN sistem SHALL menampilkan peta interaktif berbasis OpenStreetMap agar pembeli dapat menandai (pin) lokasi rumahnya.
6. WHEN pembeli menandai lokasi rumah di peta THEN sistem SHALL menghitung jarak terdekat ke lokasi COD yang ada dan menampilkan estimasi ongkos kirim (tarif ongkir per km dikonfigurasi oleh admin, contoh Rp 2.000/km).
7. WHEN ongkir dihitung THEN sistem SHALL menampilkan total biaya (harga produk + ongkir) sebelum pembeli melanjutkan ke pembayaran.
8. IF jarak rumah pembeli melebihi batas maksimal pengiriman yang dikonfigurasi admin THEN sistem SHALL menampilkan pesan bahwa lokasi di luar jangkauan pengiriman.

### Requirement 5 — Pembayaran QRIS

**User Story:** Sebagai pembeli, saya ingin membayar menggunakan QRIS, sehingga transaksi lebih mudah dan cashless.

#### Acceptance Criteria

1. WHEN pembeli mengkonfirmasi pesanan THEN sistem SHALL menampilkan kode QRIS beserta nominal yang harus dibayar dan batas waktu pembayaran (10 menit).
2. WHEN pembayaran QRIS berhasil dikonfirmasi THEN sistem SHALL memperbarui status pesanan menjadi "Dibayar" dan mengurangi stok secara permanen.
3. WHEN batas waktu pembayaran habis dan belum ada konfirmasi THEN sistem SHALL membatalkan pesanan otomatis dan melepas stok yang direservasi.
4. IF pembayaran gagal THEN sistem SHALL menampilkan notifikasi kegagalan dan memberikan opsi untuk mencoba ulang.
5. IF setelah pembayaran dikonfirmasi ternyata stok sudah habis (race condition) THEN sistem SHALL menandai pesanan sebagai "Tidak Terpenuhi" dan memproses pengembalian dana (refund).

### Requirement 6 — Notifikasi WhatsApp

**User Story:** Sebagai pembeli, saya ingin menerima notifikasi WhatsApp setelah pembayaran berhasil, sehingga saya mendapat konfirmasi dan detail pesanan saya.

#### Acceptance Criteria

1. WHEN pembayaran berhasil dikonfirmasi THEN sistem SHALL mengirimkan pesan WhatsApp ke nomor pembeli berisi ucapan terima kasih, detail pesanan, total bayar, dan jadwal COD yang dipilih.
2. WHEN pesan WhatsApp berhasil terkirim THEN sistem SHALL mencatat status pengiriman notifikasi pada data pesanan.
3. IF pengiriman pesan WhatsApp gagal THEN sistem SHALL mencatat kegagalan dan admin dapat mengirim ulang secara manual.
4. WHEN pesanan dibatalkan karena timeout THEN sistem SHALL mengirimkan notifikasi pembatalan ke nomor WhatsApp pembeli.

### Requirement 7 — Halaman Konfirmasi Pesanan

**User Story:** Sebagai pembeli, saya ingin melihat halaman konfirmasi yang jelas setelah pembayaran, sehingga saya tahu pesanan saya berhasil dan bisa menyimpan detailnya.

#### Acceptance Criteria

1. WHEN pembayaran berhasil THEN sistem SHALL menampilkan halaman konfirmasi dengan nomor order unik, detail produk yang dipesan, total bayar, lokasi COD, dan jadwal pengambilan.
2. WHEN pembeli membuka halaman konfirmasi THEN sistem SHALL menampilkan tombol untuk berbagi atau menyimpan detail pesanan.
3. WHEN pembeli mengakses nomor order THEN sistem SHALL memungkinkan pengecekan status pesanan secara mandiri.

### Requirement 8 — Mekanisme Refund

**User Story:** Sebagai pembeli, saya ingin mendapatkan pengembalian dana jika barang yang saya pesan tidak tersedia, sehingga uang saya tidak hilang.

#### Acceptance Criteria

1. WHEN pesanan berstatus "Tidak Terpenuhi" THEN sistem SHALL memicu proses refund dan memperbarui status pesanan menjadi "Refund Diproses".
2. WHEN refund diproses THEN sistem SHALL mencatat nominal refund dan mengirimkan notifikasi WhatsApp kepada pembeli.
3. IF refund tidak dapat diproses secara otomatis THEN sistem SHALL menandai pesanan untuk ditangani manual oleh admin.

### Requirement 9 — Admin Panel

**User Story:** Sebagai admin, saya ingin mengelola produk, kuota flash sale, dan jadwal COD, serta memantau semua pesanan yang masuk.

#### Acceptance Criteria

1. WHEN admin membuat flash sale baru THEN sistem SHALL memungkinkan pengaturan produk, foto, harga, kuota, dan waktu mulai/berakhir.
2. WHEN admin menambahkan lokasi dan slot COD THEN sistem SHALL menyimpan data lokasi, hari, jam mulai/berakhir, dan kapasitas maksimal per slot.
3. WHEN admin membuka daftar pesanan THEN sistem SHALL menampilkan semua pesanan beserta status (pending, dibayar, siap COD, selesai, dibatalkan).
4. WHEN admin memperbarui status pesanan THEN sistem SHALL mencatat waktu pembaruan dan mengirimkan notifikasi WhatsApp ke pembeli jika diperlukan.
5. WHEN admin ingin menyebarkan link flash sale THEN sistem SHALL menyediakan URL pendek yang bisa langsung disalin untuk dibagikan ke WhatsApp atau Instagram.
