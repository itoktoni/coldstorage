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
            'split', 'keluar_realisasi', 'keluar_detail', 'keluar',
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
            ['gudang_code' => 'GD-01', 'gudang_nama' => 'Cold Storage Utama', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_code' => 'GD-02', 'gudang_nama' => 'Cold Storage Cabang', 'created_at' => $now, 'updated_at' => $now],
            ['gudang_code' => 'GD-03', 'gudang_nama' => 'Dry Storage', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== LOKASI ==========
        DB::table('lokasi')->insert([
            // Gudang Utama (Frozen Zone)
            ['lokasi_code' => 'LOC-01', 'lokasi_nama' => 'Freezer A-1 (Daging)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-02', 'lokasi_nama' => 'Freezer A-2 (Ayam)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-03', 'lokasi_nama' => 'Freezer B-1 (Ikan)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-04', 'lokasi_nama' => 'Chiller C-1 (Dairy)', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Gudang Cabang
            ['lokasi_code' => 'LOC-05', 'lokasi_nama' => 'Freezer D-1', 'lokasi_code_gudang' => 'GD-02', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-06', 'lokasi_nama' => 'Chiller D-2', 'lokasi_code_gudang' => 'GD-02', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Dry Storage
            ['lokasi_code' => 'LOC-07', 'lokasi_nama' => 'Rak E-1 (Kentang)', 'lokasi_code_gudang' => 'GD-03', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'LOC-08', 'lokasi_nama' => 'Rak E-2 (Bumbu)', 'lokasi_code_gudang' => 'GD-03', 'lokasi_category' => null, 'created_at' => $now, 'updated_at' => $now],
            // Staging Areas
            ['lokasi_code' => 'STG-A', 'lokasi_nama' => 'Staging Area A', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'STG-B', 'lokasi_nama' => 'Staging Area B', 'lokasi_code_gudang' => 'GD-01', 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'STG-C', 'lokasi_nama' => 'Staging Area C', 'lokasi_code_gudang' => 'GD-02', 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
            ['lokasi_code' => 'STG-D', 'lokasi_nama' => 'Staging Area D', 'lokasi_code_gudang' => 'GD-02', 'lokasi_category' => 'staging', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== PRODUCT ==========
        DB::table('product')->insert([
            ['product_code' => 'PROD-01', 'product_nama' => 'Daging Sapi Qurban (kg)', 'product_harga' => 135000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-02', 'product_nama' => 'Daging Sapi Has Dalam (kg)', 'product_harga' => 180000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-03', 'product_nama' => 'Daging Sapi Tetelan (kg)', 'product_harga' => 85000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-04', 'product_nama' => 'Daging Kambing (kg)', 'product_harga' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-05', 'product_nama' => 'Ayam Utuh Frozen (kg)', 'product_harga' => 38000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-06', 'product_nama' => 'Dada Ayam Fillet (kg)', 'product_harga' => 62000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-07', 'product_nama' => 'Paha Ayam (kg)', 'product_harga' => 42000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-08', 'product_nama' => 'Sayap Ayam (kg)', 'product_harga' => 35000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-09', 'product_nama' => 'Ikan Salmon Fillet (kg)', 'product_harga' => 220000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-10', 'product_nama' => 'Ikan Kakap (kg)', 'product_harga' => 75000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-11', 'product_nama' => 'Udang Vannamei (kg)', 'product_harga' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-12', 'product_nama' => 'Cumi-cumi (kg)', 'product_harga' => 95000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-13', 'product_nama' => 'Ikan Tongkol (kg)', 'product_harga' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-14', 'product_nama' => 'Kentang Import (kg)', 'product_harga' => 28000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-15', 'product_nama' => 'Wortel (kg)', 'product_harga' => 18000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-16', 'product_nama' => 'Bawang Bombai (kg)', 'product_harga' => 22000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-17', 'product_nama' => 'Susu UHT 1L', 'product_harga' => 16000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-18', 'product_nama' => 'Keju Cheddar (kg)', 'product_harga' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-19', 'product_nama' => 'Mentega Unsalted (kg)', 'product_harga' => 95000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-20', 'product_nama' => 'Yoghurt Plain 1L', 'product_harga' => 28000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-21', 'product_nama' => 'Krim Kental Manis (kg)', 'product_harga' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-22', 'product_nama' => 'Keju Mozarella (kg)', 'product_harga' => 110000, 'created_at' => $now, 'updated_at' => $now],
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
            ['stock_code' => 'STK-20260701-0001', 'stock_id_product' => 1, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 200, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260701-0002', 'stock_id_product' => 2, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 150, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260701-0003', 'stock_id_product' => 3, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 300, 'stock_expired_date' => '2026-10-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0001', 'stock_id_product' => 4, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 80,  'stock_expired_date' => '2026-10-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0002', 'stock_id_product' => 5, 'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 500, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0003', 'stock_id_product' => 6, 'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 250, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0004', 'stock_id_product' => 7, 'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 350, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260702-0005', 'stock_id_product' => 8, 'stock_code_lokasi' => 'LOC-02', 'stock_qty' => 200, 'stock_expired_date' => '2026-09-25', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0001', 'stock_id_product' => 9,  'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 120, 'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0002', 'stock_id_product' => 10, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 180, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0003', 'stock_id_product' => 11, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 250, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0004', 'stock_id_product' => 12, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 160, 'stock_expired_date' => '2026-09-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260703-0005', 'stock_id_product' => 13, 'stock_code_lokasi' => 'LOC-03', 'stock_qty' => 300, 'stock_expired_date' => '2026-09-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260704-0001', 'stock_id_product' => 14, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 400, 'stock_expired_date' => '2026-12-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260704-0002', 'stock_id_product' => 15, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 200, 'stock_expired_date' => '2026-11-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260704-0003', 'stock_id_product' => 16, 'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 150, 'stock_expired_date' => '2026-11-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0001', 'stock_id_product' => 17, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 300, 'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0002', 'stock_id_product' => 18, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 100, 'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0003', 'stock_id_product' => 19, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 80,  'stock_expired_date' => '2026-10-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0004', 'stock_id_product' => 20, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 150, 'stock_expired_date' => '2026-08-10', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0005', 'stock_id_product' => 21, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 60,  'stock_expired_date' => '2026-09-01', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260705-0006', 'stock_id_product' => 22, 'stock_code_lokasi' => 'LOC-04', 'stock_qty' => 45,  'stock_expired_date' => '2026-08-20', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0001', 'stock_id_product' => 5,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 100, 'stock_expired_date' => '2026-09-30', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0002', 'stock_id_product' => 9,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 50,  'stock_expired_date' => '2026-09-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0003', 'stock_id_product' => 17, 'stock_code_lokasi' => 'LOC-06', 'stock_qty' => 80,  'stock_expired_date' => '2026-08-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'STK-20260706-0004', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-05', 'stock_qty' => 60,  'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== MASUK DETAIL ==========
        // in_detail_status uses MasukStatusEnum (pending/process/ready/complete)
        DB::table('masuk_detail')->insert([
            ['in_detail_code' => 'IN-20260701-0001', 'in_detail_reff' => 'POD-001', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'complete', 'in_detail_id_product' => 1,  'in_detail_qty' => 200, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260701-0002', 'in_detail_reff' => 'POD-002', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'complete', 'in_detail_id_product' => 2,  'in_detail_qty' => 150, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260702-0001', 'in_detail_reff' => 'POD-004', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'complete', 'in_detail_id_product' => 5,  'in_detail_qty' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260702-0002', 'in_detail_reff' => 'POD-005', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'complete', 'in_detail_id_product' => 6,  'in_detail_qty' => 250, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260703-0001', 'in_detail_reff' => 'POD-006', 'in_detail_tanggal' => '2026-07-03', 'in_detail_status' => 'complete', 'in_detail_id_product' => 9,  'in_detail_qty' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260705-0001', 'in_detail_reff' => null,     'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'pending',  'in_detail_id_product' => 14, 'in_detail_qty' => 400, 'created_at' => $now, 'updated_at' => $now],
            ['in_detail_code' => 'IN-20260705-0002', 'in_detail_reff' => null,     'in_detail_tanggal' => '2026-07-05', 'in_detail_status' => 'process',  'in_detail_id_product' => 17, 'in_detail_qty' => 300, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== MASUK REALISASI ==========
        DB::table('masuk_realisasi')->insert([
            ['in_realisasi_masuk_code' => 'IN-20260701-0001', 'in_realisasi_code' => 'INR-001', 'in_realisasi_id_product' => 1, 'in_realisasi_qty' => 200, 'in_realisasi_code_lokasi' => 'LOC-01', 'in_realisasi_barcode' => 'BC-001', 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260701-0002', 'in_realisasi_code' => 'INR-002', 'in_realisasi_id_product' => 2, 'in_realisasi_qty' => 150, 'in_realisasi_code_lokasi' => 'LOC-01', 'in_realisasi_barcode' => 'BC-002', 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260702-0001', 'in_realisasi_code' => 'INR-003', 'in_realisasi_id_product' => 5, 'in_realisasi_qty' => 500, 'in_realisasi_code_lokasi' => 'LOC-02', 'in_realisasi_barcode' => 'BC-003', 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260702-0002', 'in_realisasi_code' => 'INR-004', 'in_realisasi_id_product' => 6, 'in_realisasi_qty' => 250, 'in_realisasi_code_lokasi' => 'LOC-02', 'in_realisasi_barcode' => 'BC-004', 'created_at' => $now, 'updated_at' => $now],
            ['in_realisasi_masuk_code' => 'IN-20260703-0001', 'in_realisasi_code' => 'INR-005', 'in_realisasi_id_product' => 9, 'in_realisasi_qty' => 120, 'in_realisasi_code_lokasi' => 'LOC-03', 'in_realisasi_barcode' => 'BC-005', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== KELUAR ==========
        DB::table('keluar')->insert([
            ['out_code' => 'OUT-20260705-0001', 'out_tanggal' => '2026-07-05', 'out_status' => 'Done', 'out_catatan' => 'Pengiriman ke Hotel Bintang 5', 'created_at' => $now, 'updated_at' => $now],
            ['out_code' => 'OUT-20260706-0001', 'out_tanggal' => '2026-07-06', 'out_status' => 'Pending', 'out_catatan' => 'Restoran seafood Jakarta', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== KELUAR DETAIL ==========
        DB::table('keluar_detail')->insert([
            ['out_detail_code_keluar' => 'OUT-20260705-0001', 'out_detail_id_product' => 1, 'out_detail_code' => 'OUTD-001', 'out_detail_qty' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['out_detail_code_keluar' => 'OUT-20260706-0001', 'out_detail_id_product' => 9, 'out_detail_code' => 'OUTD-002', 'out_detail_qty' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== CUSTOMER & SO ==========
        DB::table('customer')->insert([
            ['customer_id' => 1, 'customer_nama' => 'Hotel Bintang 5', 'customer_alamat' => 'Jl. Sudirman No.1'],
            ['customer_id' => 2, 'customer_nama' => 'Restoran Seafood', 'customer_alamat' => 'Jl. Thamrin No.2'],
        ]);

        DB::table('so')->insert([
            ['so_tanggal' => '2026-07-01', 'so_code' => 'SO-20260701-0001', 'so_id_customer' => 1, 'so_status' => 'Closed', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('detail_so')->insert([
            ['so_detail_id_so' => 1, 'so_detail_id_product' => 1, 'so_detail_qty' => 50, 'so_detail_code' => 'SOD-001', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== CATEGORIES ==========
        DB::table('categories')->upsert([
            ['slug' => 'daging', 'name' => 'Daging', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ayam',   'name' => 'Ayam',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ikan',   'name' => 'Ikan',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sayuran','name' => 'Sayuran','created_at' => $now, 'updated_at' => $now],
            ['slug' => 'dairy',  'name' => 'Dairy',  'created_at' => $now, 'updated_at' => $now],
        ], ['slug'], ['name', 'created_at', 'updated_at']);
    }
}
