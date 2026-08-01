<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ========== GUDANG ==========
        DB::table('gudang')->insert([
            ['gudang_nama' => 'Cold Storage Utama', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_nama' => 'Cold Storage Cabang', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_nama' => 'Dry Storage', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== LOKASI ==========
        DB::table('lokasi')->insert([
            // Gudang Utama (Frozen Zone)
            ['lokasi_nama' => 'Freezer A-1 (Daging)', 'lokasi_id_gudang' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_nama' => 'Freezer A-2 (Ayam)', 'lokasi_id_gudang' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_nama' => 'Freezer B-1 (Ikan)', 'lokasi_id_gudang' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_nama' => 'Chiller C-1 (Dairy)', 'lokasi_id_gudang' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Gudang Cabang
            ['lokasi_nama' => 'Freezer D-1', 'lokasi_id_gudang' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_nama' => 'Chiller D-2', 'lokasi_id_gudang' => 2, 'created_at' => $now, 'updated_at' => $now],
            // Dry Storage
            ['lokasi_nama' => 'Rak E-1 (Kentang)', 'lokasi_id_gudang' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_nama' => 'Rak E-2 (Bumbu)', 'lokasi_id_gudang' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== PRODUCT ==========
        DB::table('product')->insert([
            // Daging
            ['product_nama' => 'Daging Sapi Qurban (kg)', 'product_harga' => 135000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Daging Sapi Has Dalam (kg)', 'product_harga' => 180000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Daging Sapi Tetelan (kg)', 'product_harga' => 85000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Daging Kambing (kg)', 'product_harga' => 150000, 'created_at' => $now, 'updated_at' => $now],
            // Ayam
            ['product_nama' => 'Ayam Utuh Frozen (kg)', 'product_harga' => 38000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Dada Ayam Fillet (kg)', 'product_harga' => 62000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Paha Ayam (kg)', 'product_harga' => 42000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Sayap Ayam (kg)', 'product_harga' => 35000, 'created_at' => $now, 'updated_at' => $now],
            // Ikan
            ['product_nama' => 'Ikan Salmon Fillet (kg)', 'product_harga' => 220000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Ikan Kakap (kg)', 'product_harga' => 75000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Udang Vannamei (kg)', 'product_harga' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Cumi-cumi (kg)', 'product_harga' => 95000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Ikan Tongkol (kg)', 'product_harga' => 45000, 'created_at' => $now, 'updated_at' => $now],
            // Kentang & Sayuran
            ['product_nama' => 'Kentang Import (kg)', 'product_harga' => 28000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Wortel (kg)', 'product_harga' => 18000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Bawang Bombai (kg)', 'product_harga' => 22000, 'created_at' => $now, 'updated_at' => $now],
            // Dairy
            ['product_nama' => 'Susu UHT 1L', 'product_harga' => 16000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Keju Cheddar (kg)', 'product_harga' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Mentega Blueband (kg)', 'product_harga' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Yoghurt Plain 500ml', 'product_harga' => 25000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Keju Mozzarella (kg)', 'product_harga' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['product_nama' => 'Cream Cheese (kg)', 'product_harga' => 110000, 'created_at' => $now, 'updated_at' => $now],
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
        DB::table('stock')->insert([
            // Daging
            ['stock_code' => 'STK-20260701-0001', 'stock_id_product' => 1, 'stock_id_lokasi' => 1, 'stock_qty' => 200, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260701-0002', 'stock_id_product' => 2, 'stock_id_lokasi' => 1, 'stock_qty' => 150, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260701-0003', 'stock_id_product' => 3, 'stock_id_lokasi' => 1, 'stock_qty' => 300, 'stock_expired_date' => '2026-10-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0001', 'stock_id_product' => 4, 'stock_id_lokasi' => 1, 'stock_qty' => 80, 'stock_expired_date' => '2026-10-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Ayam
            ['stock_code' => 'STK-20260702-0002', 'stock_id_product' => 5, 'stock_id_lokasi' => 2, 'stock_qty' => 500, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0003', 'stock_id_product' => 6, 'stock_id_lokasi' => 2, 'stock_qty' => 250, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0004', 'stock_id_product' => 7, 'stock_id_lokasi' => 2, 'stock_qty' => 350, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0005', 'stock_id_product' => 8, 'stock_id_lokasi' => 2, 'stock_qty' => 200, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Ikan
            ['stock_code' => 'STK-20260703-0001', 'stock_id_product' => 9,  'stock_id_lokasi' => 3, 'stock_qty' => 120, 'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0002', 'stock_id_product' => 10, 'stock_id_lokasi' => 3, 'stock_qty' => 180, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0003', 'stock_id_product' => 11, 'stock_id_lokasi' => 3, 'stock_qty' => 250, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0004', 'stock_id_product' => 12, 'stock_id_lokasi' => 3, 'stock_qty' => 160, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0005', 'stock_id_product' => 13, 'stock_id_lokasi' => 3, 'stock_qty' => 300, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Kentang & Sayuran
            ['stock_code' => 'STK-20260704-0001', 'stock_id_product' => 14, 'stock_id_lokasi' => 7, 'stock_qty' => 400, 'stock_expired_date' => '2026-12-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260704-0002', 'stock_id_product' => 15, 'stock_id_lokasi' => 7, 'stock_qty' => 200, 'stock_expired_date' => '2026-11-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260704-0003', 'stock_id_product' => 16, 'stock_id_lokasi' => 7, 'stock_qty' => 150, 'stock_expired_date' => '2026-11-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Dairy
            ['stock_code' => 'STK-20260705-0001', 'stock_id_product' => 17, 'stock_id_lokasi' => 4, 'stock_qty' => 300, 'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0002', 'stock_id_product' => 18, 'stock_id_lokasi' => 4, 'stock_qty' => 100, 'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0003', 'stock_id_product' => 19, 'stock_id_lokasi' => 4, 'stock_qty' => 80, 'stock_expired_date' => '2026-10-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0004', 'stock_id_product' => 20, 'stock_id_lokasi' => 4, 'stock_qty' => 150, 'stock_expired_date' => '2026-08-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0005', 'stock_id_product' => 21, 'stock_id_lokasi' => 4, 'stock_qty' => 60, 'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0006', 'stock_id_product' => 22, 'stock_id_lokasi' => 4, 'stock_qty' => 45, 'stock_expired_date' => '2026-08-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            // Cabang stock
            ['stock_code' => 'STK-20260706-0001', 'stock_id_product' => 5, 'stock_id_lokasi' => 5, 'stock_qty' => 100, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0002', 'stock_id_product' => 9, 'stock_id_lokasi' => 5, 'stock_qty' => 50, 'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0003', 'stock_id_product' => 17, 'stock_id_lokasi' => 6, 'stock_qty' => 80, 'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0004', 'stock_id_product' => 1, 'stock_id_lokasi' => 5, 'stock_qty' => 60, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== MASUK DETAIL ==========
        DB::table('masuk_detail')->insert([
            ['in_detail_code' => 'IN-20260701-0001', 'in_detail_reff' => 'POD-001', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'Done', 'in_detail_id_product' => 1, 'in_detail_qty' => 200, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260701-0002', 'in_detail_reff' => 'POD-002', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'Done', 'in_detail_id_product' => 2, 'in_detail_qty' => 150, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260702-0001', 'in_detail_reff' => 'POD-004', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'Done', 'in_detail_id_product' => 5, 'in_detail_qty' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260702-0002', 'in_detail_reff' => 'POD-005', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'Done', 'in_detail_id_product' => 6, 'in_detail_qty' => 250, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260703-0001', 'in_detail_reff' => 'POD-006', 'in_detail_tanggal' => '2026-07-03', 'in_detail_status' => 'Done', 'in_detail_id_product' => 9, 'in_detail_qty' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260705-0001', 'in_detail_reff' => null, 'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'Pending', 'in_detail_id_product' => 14, 'in_detail_qty' => 400, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260705-0002', 'in_detail_reff' => null, 'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'In Progress', 'in_detail_id_product' => 17, 'in_detail_qty' => 300, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== MASUK REALISASI ==========
        DB::table('masuk_realisasi')->insert([
            ['in_realisasi_masuk_code' => 'IN-20260701-0001', 'in_realisasi_code' => 'INR-001', 'in_realisasi_id_product' => 1, 'in_realisasi_qty' => 200, 'in_realisasi_id_lokasi' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260701-0002', 'in_realisasi_code' => 'INR-002', 'in_realisasi_id_product' => 2, 'in_realisasi_qty' => 150, 'in_realisasi_id_lokasi' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260702-0001', 'in_realisasi_code' => 'INR-003', 'in_realisasi_id_product' => 5, 'in_realisasi_qty' => 500, 'in_realisasi_id_lokasi' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260702-0002', 'in_realisasi_code' => 'INR-004', 'in_realisasi_id_product' => 6, 'in_realisasi_qty' => 250, 'in_realisasi_id_lokasi' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260703-0001', 'in_realisasi_code' => 'INR-005', 'in_realisasi_id_product' => 9, 'in_realisasi_qty' => 120, 'in_realisasi_id_lokasi' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== KELUAR ==========
        DB::table('keluar')->insert([
            ['out_code' => 'OUT-20260705-0001', 'out_tanggal' => '2026-07-05', 'out_status' => 'Done', 'out_catatan' => 'Pengiriman ke Hotel Bintang 5', 'created_at' => $now, 'updated_at' => $now],
            ['out_code' => 'OUT-20260706-0001', 'out_tanggal' => '2026-07-06', 'out_status' => 'Pending', 'out_catatan' => 'Restoran seafood Jakarta', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('keluar_detail')->insert([
            ['out_detail_code_keluar' => 'OUT-20260705-0001', 'out_detail_id_product' => 1, 'out_detail_code' => 'OD-001', 'out_detail_qty' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['out_detail_code_keluar' => 'OUT-20260705-0001', 'out_detail_id_product' => 5, 'out_detail_code' => 'OD-002', 'out_detail_qty' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['out_detail_code_keluar' => 'OUT-20260706-0001', 'out_detail_id_product' => 9, 'out_detail_code' => 'OD-003', 'out_detail_qty' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('keluar_realisasi')->insert([
            ['out_realisasi_id_detail' => 1, 'out_realisasi_code' => 'OR-001', 'out_realisasi_id_stock' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['out_realisasi_id_detail' => 2, 'out_realisasi_code' => 'OR-002', 'out_realisasi_id_stock' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== SPLIT ==========
        DB::table('split')->insert([
            ['split_id_product' => 5, 'split_id_stock' => 5, 'split_qty_new' => 450, 'split_qty_old' => 500, 'split_qty_waste' => 50, 'split_tanggal' => '2026-07-04', 'created_at' => $now, 'updated_at' => $now],
            ['split_id_product' => 1, 'split_id_stock' => 1, 'split_qty_new' => 180, 'split_qty_old' => 200, 'split_qty_waste' => 20, 'split_tanggal' => '2026-07-02', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== CUSTOMER ==========
        DB::table('customer')->insert([
            ['customer_nama' => 'Hotel Grand Hyatt Jakarta', 'customer_telepon' => '021-5711111', 'customer_alamat' => 'Jl. MH Thamrin, Jakarta Pusat', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Restoran Seafood Pantai Indah', 'customer_telepon' => '021-5551234', 'customer_alamat' => 'Pantai Indah Kapuk, Jakarta Utara', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'PT Catering Nusantara', 'customer_telepon' => '021-8887654', 'customer_alamat' => 'Jl. Sudirman Kav. 52-53, Jakarta Selatan', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Supermarket segar Fresh Market', 'customer_telepon' => '021-4567890', 'customer_alamat' => 'Jl. Kemang Raya, Jakarta Selatan', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Restoran Sate Khas Senayan', 'customer_telepon' => '021-57901234', 'customer_alamat' => 'Jl. Asia Afrika, Senayan, Jakarta Pusat', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Hotel Pullman Jakarta', 'customer_telepon' => '021-3901234', 'customer_alamat' => 'Jl. M.H. Thamrin, Jakarta Pusat', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Warung Bakso Mas Ade', 'customer_telepon' => '0812-9876-5432', 'customer_alamat' => 'Jl. Pahlawan No. 10, Bandung', 'created_at' => $now, 'updated_at' => $now],
            ['customer_nama' => 'Toko Bahan Kue Mama Lia', 'customer_telepon' => '0856-1234-5678', 'customer_alamat' => 'Jl. Raya Bogor Km 30, Jakarta Timur', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== SO (Sales Order) ==========
        DB::table('so')->insert([
            ['so_tanggal' => '2026-07-04', 'so_code' => 'SO-20260704-0001', 'so_id_customer' => 1, 'so_status' => 'Done', 'so_keterangan' => 'Pengiriman mingguan hotel', 'created_at' => $now, 'updated_at' => $now],
            ['so_tanggal' => '2026-07-05', 'so_code' => 'SO-20260705-0001', 'so_id_customer' => 2, 'so_status' => 'Done', 'so_keterangan' => 'Restoran seafood weekly order', 'created_at' => $now, 'updated_at' => $now],
            ['so_tanggal' => '2026-07-06', 'so_code' => 'SO-20260706-0001', 'so_id_customer' => 3, 'so_status' => 'Pending', 'so_keterangan' => 'Catering acara seminar', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('detail_so')->insert([
            ['so_detail_id_so' => 1, 'so_detail_id_product' => 1, 'so_detail_qty' => 50, 'so_detail_harga' => 135000, 'so_detail_code' => 'SOD-001', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 1, 'so_detail_id_product' => 5, 'so_detail_qty' => 100, 'so_detail_harga' => 38000, 'so_detail_code' => 'SOD-002', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 2, 'so_detail_id_product' => 9, 'so_detail_qty' => 30, 'so_detail_harga' => 220000, 'so_detail_code' => 'SOD-003', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 2, 'so_detail_id_product' => 11, 'so_detail_qty' => 20, 'so_detail_harga' => 120000, 'so_detail_code' => 'SOD-004', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 3, 'so_detail_id_product' => 1, 'so_detail_qty' => 80, 'so_detail_harga' => 135000, 'so_detail_code' => 'SOD-005', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 3, 'so_detail_id_product' => 6, 'so_detail_qty' => 40, 'so_detail_harga' => 62000, 'so_detail_code' => 'SOD-006', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 3, 'so_detail_id_product' => 18, 'so_detail_qty' => 10, 'so_detail_harga' => 120000, 'so_detail_code' => 'SOD-007', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
