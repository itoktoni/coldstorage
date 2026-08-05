<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Make the seeder re-runnable: clear existing WMS demo data first
        // (children before parents, FK checks disabled to avoid ordering issues).
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'split_detail', 'split', 'keluar_realisasi', 'keluar_detail', 'keluar',
            'masuk_realisasi', 'masuk_detail',
            'detail_so', 'so', 'customer',
            'detail_po', 'po', 'supplier',
            'stock', 'lokasi', 'gudang', 'product',
        ] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ========== GUDANG ==========
        DB::table('gudang')->insert([
            ['gudang_code' => 'GD-01', 'gudang_nama' => 'Cold Storage A', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_code' => 'GD-02', 'gudang_nama' => 'Cold Storage B', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_code' => 'GD-03', 'gudang_nama' => 'Dry Storage', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_code' => 'GD-04', 'gudang_nama' => 'Retail Area', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== LOKASI ==========
        DB::table('lokasi')->insert([
            // Cold Storage A
            ['lokasi_code' => 'LOC-01', 'lokasi_nama' => 'RACK A1 (Daging)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-02', 'lokasi_nama' => 'RACK A2 (Ayam)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-03', 'lokasi_nama' => 'RACK A3 (Ikan)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-04', 'lokasi_nama' => 'RACK A4 (Dairy)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            // Cold Storage B
            ['lokasi_code' => 'LOC-05', 'lokasi_nama' => 'RACK B1', 'lokasi_code_gudang' => 'GD-02', 'lokasi_max_qty' => 1000, 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-06', 'lokasi_nama' => 'RACK B2', 'lokasi_code_gudang' => 'GD-02', 'lokasi_max_qty' => 1000, 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Dry Storage
            ['lokasi_code' => 'LOC-07', 'lokasi_nama' => 'RACK C1 (Sayuran)', 'lokasi_code_gudang' => 'GD-03', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-08', 'lokasi_nama' => 'RACK C2', 'lokasi_code_gudang' => 'GD-03', 'lokasi_max_qty' => 1000, 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Retail Area
            ['lokasi_code' => 'LOC-09', 'lokasi_nama' => 'RACK D1', 'lokasi_code_gudang' => 'GD-04', 'lokasi_max_qty' => 1000, 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-10', 'lokasi_nama' => 'RACK D2', 'lokasi_code_gudang' => 'GD-04', 'lokasi_max_qty' => 1000, 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Staging Areas
            ['lokasi_code' => 'LOC-A', 'lokasi_nama' => 'Staging Area A', 'lokasi_code_gudang' => 'GD-01', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-B', 'lokasi_nama' => 'Staging Area B', 'lokasi_code_gudang' => 'GD-02', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-C', 'lokasi_nama' => 'Staging Area C', 'lokasi_code_gudang' => 'GD-03', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-D', 'lokasi_nama' => 'Staging Area D', 'lokasi_code_gudang' => 'GD-04', 'lokasi_max_qty' => 1000, 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== PRODUCT ==========
        // product_category = slug dari tabel `categories` (daging/ayam/ikan/sayuran/dairy),
        // dipakai rekomendasi rack (canAcceptCategory) — lihat agent.md §1
        DB::table('product')->insert([
            ['product_code' => 'PROD-01', 'product_nama' => 'Iga Sapi (kg)', 'product_harga' => 135000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-02', 'product_nama' => 'Daging Sapi Has Dalam (kg)', 'product_harga' => 180000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-03', 'product_nama' => 'Daging Sapi Tetelan (kg)', 'product_harga' => 85000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-04', 'product_nama' => 'Daging Kambing (kg)', 'product_harga' => 150000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-05', 'product_nama' => 'Ayam Utuh Frozen (kg)', 'product_harga' => 38000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-06', 'product_nama' => 'Dada Ayam Fillet (kg)', 'product_harga' => 62000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-07', 'product_nama' => 'Paha Ayam (kg)', 'product_harga' => 42000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-08', 'product_nama' => 'Sayap Ayam (kg)', 'product_harga' => 35000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-09', 'product_nama' => 'Ikan Salmon Fillet (kg)', 'product_harga' => 220000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-10', 'product_nama' => 'Ikan Kakap (kg)', 'product_harga' => 75000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-11', 'product_nama' => 'Udang Vannamei (kg)', 'product_harga' => 120000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-12', 'product_nama' => 'Cumi-cumi (kg)', 'product_harga' => 95000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-13', 'product_nama' => 'Ikan Tongkol (kg)', 'product_harga' => 45000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-14', 'product_nama' => 'Kentang Import (kg)', 'product_harga' => 28000, 'product_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-15', 'product_nama' => 'Wortel (kg)', 'product_harga' => 18000, 'product_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-16', 'product_nama' => 'Bawang Bombai (kg)', 'product_harga' => 22000, 'product_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-17', 'product_nama' => 'Susu UHT 1L', 'product_harga' => 16000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-18', 'product_nama' => 'Keju Cheddar (kg)', 'product_harga' => 120000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-19', 'product_nama' => 'Mentega Unsalted (kg)', 'product_harga' => 95000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-20', 'product_nama' => 'Yoghurt Plain 1L', 'product_harga' => 28000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-21', 'product_nama' => 'Krim Kental Manis (kg)', 'product_harga' => 45000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-22', 'product_nama' => 'Keju Mozarella (kg)', 'product_harga' => 110000, 'product_category' => 'dairy', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-23', 'product_nama' => 'Sirloin Slice (kg)', 'product_harga' => 210000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-24', 'product_nama' => 'Daging Giling (kg)', 'product_harga' => 95000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-25', 'product_nama' => 'Has Dalam Slice (kg)', 'product_harga' => 220000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-26', 'product_nama' => 'Tetelan Sapi (kg)', 'product_harga' => 75000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== CUSTOMER & SO ==========
        DB::table('customer')->insert([
            ['customer_id' => 1, 'customer_nama' => 'Hotel Bintang 5', 'customer_alamat' => 'Jl. Sudirman No.1'],
            ['customer_id' => 2, 'customer_nama' => 'Restoran Seafood', 'customer_alamat' => 'Jl. Thamrin No.2'],
        ]);

        // ========== SUPPLIER ==========
        DB::table('supplier')->insert([
            ['supplier_id' => 1, 'supplier_nama' => 'PT Daging Segar Indonesia'],
            ['supplier_id' => 2, 'supplier_nama' => 'CV Ayam Nusantara'],
            ['supplier_id' => 3, 'supplier_nama' => 'PT Laut Biru Perkasa'],
            ['supplier_id' => 4, 'supplier_nama' => 'PT Dairy Mandiri'],
            ['supplier_id' => 5, 'supplier_nama' => 'UD Kentang Jaya'],
        ]);

        // ========== PO ==========
        DB::table('po')->insert([
            ['po_tanggal' => '2026-07-01', 'po_code' => 'PO-20260701-0001', 'po_id_supplier' => 1, 'po_status' => 'Closed', 'created_at' => $now, 'updated_at' => $now],
            ['po_tanggal' => '2026-07-02', 'po_code' => 'PO-20260702-0001', 'po_id_supplier' => 2, 'po_status' => 'Closed', 'created_at' => $now, 'updated_at' => $now],
            ['po_tanggal' => '2026-07-03', 'po_code' => 'PO-20260703-0001', 'po_id_supplier' => 3, 'po_status' => 'Ordered', 'created_at' => $now, 'updated_at' => $now],
            ['po_tanggal' => '2026-07-10', 'po_code' => 'PO-20260710-0001', 'po_id_supplier' => 4, 'po_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
            ['po_tanggal' => '2026-07-15', 'po_code' => 'PO-20260715-0001', 'po_id_supplier' => 5, 'po_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== DETAIL PO ==========
        DB::table('detail_po')->insert([
            ['po_detail_id_po' => 1, 'po_detail_id_product' => 1, 'po_detail_qty' => 200, 'po_detail_code' => 'POD-001', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 1, 'po_detail_id_product' => 2, 'po_detail_qty' => 150, 'po_detail_code' => 'POD-002', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 1, 'po_detail_id_product' => 3, 'po_detail_qty' => 300, 'po_detail_code' => 'POD-003', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 2, 'po_detail_id_product' => 5, 'po_detail_qty' => 500, 'po_detail_code' => 'POD-004', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 2, 'po_detail_id_product' => 6, 'po_detail_qty' => 250, 'po_detail_code' => 'POD-005', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 3, 'po_detail_id_product' => 9,  'po_detail_qty' => 120, 'po_detail_code' => 'POD-006', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 3, 'po_detail_id_product' => 11, 'po_detail_qty' => 250, 'po_detail_code' => 'POD-007', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 4, 'po_detail_id_product' => 17, 'po_detail_qty' => 300, 'po_detail_code' => 'POD-008', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 4, 'po_detail_id_product' => 18, 'po_detail_qty' => 100, 'po_detail_code' => 'POD-009', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 4, 'po_detail_id_product' => 21, 'po_detail_qty' => 60,  'po_detail_code' => 'POD-010', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 5, 'po_detail_id_product' => 14, 'po_detail_qty' => 400, 'po_detail_code' => 'POD-011', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 5, 'po_detail_id_product' => 15, 'po_detail_qty' => 200, 'po_detail_code' => 'POD-012', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== STOCK ==========
        // stock_code mengikuti konvensi barcode = diawali product_code (PROD-xx),
        // konsisten dengan BarcodeController::postGenerate (implode product_code#timestamp#qty#exp).
        // stock_pallet_code mengikuti MasukRealisasi::generateGroupCode() = 'PAL-<Ymd>-<6digit>'
        // (sama seperti kode pallet yg tampil saat status READY di halaman realisasikan).
        // Satu pallet = gabungan total produk di lokasi yang sama (mis. PAL-20260701-000101 =
        // PROD-01 @ LOC-01, total 200+50=250).
        DB::table('stock')->insert([
            ['stock_code' => 'PROD-01#202607010001#200#20261015', 'stock_pallet_code' => 'PAL-20260701-000101', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 200, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-02#202607010002#150#20261015', 'stock_pallet_code' => 'PAL-20260701-000102', 'stock_id_product' => 2,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 150, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-03#202607010003#300#20261020', 'stock_pallet_code' => 'PAL-20260701-000103', 'stock_id_product' => 3,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 300, 'stock_expired_date' => '2026-10-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-04#202607020001#80#20261010', 'stock_pallet_code' => 'PAL-20260702-000104', 'stock_id_product' => 4,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 80,  'stock_expired_date' => '2026-10-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-05#202607020002#500#20260930', 'stock_pallet_code' => 'PAL-20260702-000105', 'stock_id_product' => 5,  'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 500, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-06#202607020003#250#20260930', 'stock_pallet_code' => 'PAL-20260702-000106', 'stock_id_product' => 6,  'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 250, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-07#202607020004#350#20260925', 'stock_pallet_code' => 'PAL-20260702-000107', 'stock_id_product' => 7,  'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 350, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-08#202607020005#200#20260925', 'stock_pallet_code' => 'PAL-20260702-000108', 'stock_id_product' => 8,  'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 200, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-09#202607030001#120#20260915', 'stock_pallet_code' => 'PAL-20260703-000109', 'stock_id_product' => 9,  'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 120, 'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-10#202607030002#180#20260920', 'stock_pallet_code' => 'PAL-20260703-000110', 'stock_id_product' => 10, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 180, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-11#202607030003#250#20260910', 'stock_pallet_code' => 'PAL-20260703-000111', 'stock_id_product' => 11, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 250, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-12#202607030004#160#20260910', 'stock_pallet_code' => 'PAL-20260703-000112', 'stock_id_product' => 12, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 160, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-13#202607030005#300#20260920', 'stock_pallet_code' => 'PAL-20260703-000113', 'stock_id_product' => 13, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 300, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-14#202607040001#400#20261201', 'stock_pallet_code' => 'PAL-20260704-000114', 'stock_id_product' => 14, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 400, 'stock_expired_date' => '2026-12-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-15#202607040002#200#20261115', 'stock_pallet_code' => 'PAL-20260704-000115', 'stock_id_product' => 15, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 200, 'stock_expired_date' => '2026-11-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-16#202607040003#150#20261101', 'stock_pallet_code' => 'PAL-20260704-000116', 'stock_id_product' => 16, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 150, 'stock_expired_date' => '2026-11-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-17#202607050001#300#20260815', 'stock_pallet_code' => 'PAL-20260705-000117', 'stock_id_product' => 17, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 300, 'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-18#202607050002#100#20260901', 'stock_pallet_code' => 'PAL-20260705-000118', 'stock_id_product' => 18, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 100, 'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-19#202607050003#80#20261001', 'stock_pallet_code' => 'PAL-20260705-000119', 'stock_id_product' => 19, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 80,  'stock_expired_date' => '2026-10-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-20#202607050004#150#20260810', 'stock_pallet_code' => 'PAL-20260705-000120', 'stock_id_product' => 20, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 150, 'stock_expired_date' => '2026-08-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-21#202607050005#60#20260901', 'stock_pallet_code' => 'PAL-20260705-000121', 'stock_id_product' => 21, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 60,  'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-22#202607050006#45#20260820', 'stock_pallet_code' => 'PAL-20260705-000122', 'stock_id_product' => 22, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 45,  'stock_expired_date' => '2026-08-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-05#202607060001#100#20260930', 'stock_pallet_code' => 'PAL-20260706-000123', 'stock_id_product' => 5,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 100, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-09#202607060002#50#20260915', 'stock_pallet_code' => 'PAL-20260706-000124', 'stock_id_product' => 9,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 50,  'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-17#202607060003#80#20260815', 'stock_pallet_code' => 'PAL-20260706-000125', 'stock_id_product' => 17, 'stock_code_lokasi' => 'LOC-06', 'stock_qty' => 80,  'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-01#202607060004#60#20261015', 'stock_pallet_code' => 'PAL-20260706-000126', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 60,  'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Source stock for split testing (satu pallet dgn stok utama PROD-01/PROD-02 di LOC-01)
            ['stock_code' => 'PROD-01#202608040001#50#20261015', 'stock_pallet_code' => 'PAL-20260701-000101', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 50,  'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-02#202608040002#30#20261015', 'stock_pallet_code' => 'PAL-20260701-000102', 'stock_id_product' => 2,  'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 30,  'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== MASUK DETAIL ==========
        // in_detail_status uses MasukStatusEnum (pending/process/ready/complete)
        // DB::table('masuk_detail')->insert([
        //     ['in_detail_code' => 'IN-20260701-0001', 'in_detail_reff' => 'POD-001', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'complete', 'in_detail_id_product' => 1,  'in_detail_qty' => 200, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260701-0002', 'in_detail_reff' => 'POD-002', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'complete', 'in_detail_id_product' => 2,  'in_detail_qty' => 150, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260702-0001', 'in_detail_reff' => 'POD-004', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'complete', 'in_detail_id_product' => 5,  'in_detail_qty' => 500, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260702-0002', 'in_detail_reff' => 'POD-005', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'complete', 'in_detail_id_product' => 6,  'in_detail_qty' => 250, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260703-0001', 'in_detail_reff' => 'POD-006', 'in_detail_tanggal' => '2026-07-03', 'in_detail_status' => 'complete', 'in_detail_id_product' => 9,  'in_detail_qty' => 120, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260705-0001', 'in_detail_reff' => null,     'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'pending',  'in_detail_id_product' => 14, 'in_detail_qty' => 400, 'created_at' => $now, 'updated_at' => $now],
        //     ['in_detail_code' => 'IN-20260705-0002', 'in_detail_reff' => null,     'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'process',  'in_detail_id_product' => 17, 'in_detail_qty' => 300, 'created_at' => $now, 'updated_at' => $now],
        // ]);

        // // ========== MASUK REALISASI ==========
        // // in_realisasi_group = kode pallet (PAL-xxx), konsisten dengan stock_pallet_code.
        // // in_realisasi_barcode = barcode scanned → jadi stock_code (format: PROD-xx#ts#qty#exp).
        // DB::table('masuk_realisasi')->insert([
        //     ['in_realisasi_masuk_code' => 'IN-20260701-0001', 'in_realisasi_code' => 'INR-001', 'in_realisasi_id_product' => 1, 'in_realisasi_qty' => 200, 'in_realisasi_code_lokasi' => 'LOC-01', 'in_realisasi_barcode' => 'PROD-01#202607010001#200#20261015', 'in_realisasi_group' => 'PAL-20260701-000101', 'created_at' => $now, 'updated_at' => $now],
        //     ['in_realisasi_masuk_code' => 'IN-20260701-0002', 'in_realisasi_code' => 'INR-002', 'in_realisasi_id_product' => 2, 'in_realisasi_qty' => 150, 'in_realisasi_code_lokasi' => 'LOC-01', 'in_realisasi_barcode' => 'PROD-02#202607010002#150#20261015', 'in_realisasi_group' => 'PAL-20260701-000102', 'created_at' => $now, 'updated_at' => $now],
        //     ['in_realisasi_masuk_code' => 'IN-20260702-0001', 'in_realisasi_code' => 'INR-003', 'in_realisasi_id_product' => 5, 'in_realisasi_qty' => 500, 'in_realisasi_code_lokasi' => 'LOC-02', 'in_realisasi_barcode' => 'PROD-05#202607020002#500#20260930', 'in_realisasi_group' => 'PAL-20260702-000105', 'created_at' => $now, 'updated_at' => $now],
        //     ['in_realisasi_masuk_code' => 'IN-20260702-0002', 'in_realisasi_code' => 'INR-004', 'in_realisasi_id_product' => 6, 'in_realisasi_qty' => 250, 'in_realisasi_code_lokasi' => 'LOC-02', 'in_realisasi_barcode' => 'PROD-06#202607020003#250#20260930', 'in_realisasi_group' => 'PAL-20260702-000106', 'created_at' => $now, 'updated_at' => $now],
        //     ['in_realisasi_masuk_code' => 'IN-20260703-0001', 'in_realisasi_code' => 'INR-005', 'in_realisasi_id_product' => 9, 'in_realisasi_qty' => 120, 'in_realisasi_code_lokasi' => 'LOC-03', 'in_realisasi_barcode' => 'PROD-09#202607030001#120#20260915', 'in_realisasi_group' => 'PAL-20260703-000109', 'created_at' => $now, 'updated_at' => $now],
        // ]);

        // // ========== KELUAR ==========
        // DB::table('keluar')->insert([
        //     ['out_code' => 'OUT-20260705-0001', 'out_tanggal' => '2026-07-05', 'out_status' => 'Done', 'out_catatan' => 'Pengiriman ke Hotel Bintang 5', 'created_at' => $now, 'updated_at' => $now],
        //     ['out_code' => 'OUT-20260706-0001', 'out_tanggal' => '2026-07-06', 'out_status' => 'Pending', 'out_catatan' => 'Restoran seafood Jakarta', 'created_at' => $now, 'updated_at' => $now],
        // ]);

        // // ========== KELUAR DETAIL ==========
        // DB::table('keluar_detail')->insert([
        //     ['out_detail_code_keluar' => 'OUT-20260705-0001', 'out_detail_id_product' => 1, 'out_detail_code' => 'OUTD-001', 'out_detail_qty' => 50, 'created_at' => $now, 'updated_at' => $now],
        //     ['out_detail_code_keluar' => 'OUT-20260706-0001', 'out_detail_id_product' => 9, 'out_detail_code' => 'OUTD-002', 'out_detail_qty' => 30, 'created_at' => $now, 'updated_at' => $now],
        // ]);


        // DB::table('so')->insert([
        //     ['so_tanggal' => '2026-07-01', 'so_code' => 'SO-20260701-0001', 'so_id_customer' => 1, 'so_status' => 'Closed', 'created_at' => $now, 'updated_at' => $now],
        // ]);

        // DB::table('detail_so')->insert([
        //     ['so_detail_id_so' => 1, 'so_detail_id_product' => 1, 'so_detail_qty' => 50, 'so_detail_code' => 'SOD-001', 'created_at' => $now, 'updated_at' => $now],
        // ]);

        // ========== CATEGORIES ==========
        DB::table('categories')->upsert([
            ['slug' => 'daging', 'name' => 'Daging', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ayam',   'name' => 'Ayam',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ikan',   'name' => 'Ikan',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sayuran', 'name' => 'Sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'dairy',  'name' => 'Dairy',  'created_at' => $now, 'updated_at' => $now],
        ], ['slug'], ['name', 'created_at', 'updated_at']);
    }
}
