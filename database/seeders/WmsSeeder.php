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
            'so_prepare_detail', 'so_prepare', 'stock_assignment', 'stock_log', 'forklift_task',
            'invoice_detail', 'invoice',
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
            ['product_code' => 'PROD-01', 'product_nama' => 'Kentang 10kg (karton)', 'product_harga' => 100000, 'product_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-02', 'product_nama' => 'Kentang 2.5kg (Pack)', 'product_harga' => 25000, 'product_category' => 'sayuran', 'created_at' => $now, 'updated_at' => $now],

            ['product_code' => 'PROD-03', 'product_nama' => 'Daging Sapi Has Dalam (kg)', 'product_harga' => 100000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-04', 'product_nama' => 'Daging Giling (kg)', 'product_harga' => 20000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-05', 'product_nama' => 'Has Dalam Slice (kg)', 'product_harga' => 150000, 'product_category' => 'daging', 'created_at' => $now, 'updated_at' => $now],

            ['product_code' => 'PROD-06', 'product_nama' => 'Ayam Utuh Frozen (kg)', 'product_harga' => 38000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-07', 'product_nama' => 'Dada Ayam Fillet (kg)', 'product_harga' => 62000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-08', 'product_nama' => 'Paha Ayam (kg)', 'product_harga' => 42000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-09', 'product_nama' => 'Sayap Ayam (kg)', 'product_harga' => 35000, 'product_category' => 'ayam', 'created_at' => $now, 'updated_at' => $now],
            ['product_code' => 'PROD-10', 'product_nama' => 'Ikan Salmon Fillet (kg)', 'product_harga' => 220000, 'product_category' => 'ikan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== CUSTOMER & SO ==========
        DB::table('customer')->insert([
            ['customer_id' => 1, 'customer_nama' => 'Hotel Bintang 5', 'customer_alamat' => 'Jl. Sudirman No.1'],
            ['customer_id' => 2, 'customer_nama' => 'Restoran Seafood', 'customer_alamat' => 'Jl. Thamrin No.2'],
            ['customer_id' => 3, 'customer_nama' => 'Bapak Rahmat', 'customer_alamat' => 'Jl. Bekasi raya No.2'],
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
            ['po_tanggal' => '2026-07-01', 'po_code' => 'PO-20260701-0001', 'po_id_supplier' => 4, 'po_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
            ['po_tanggal' => '2026-07-02', 'po_code' => 'PO-20260702-0001', 'po_id_supplier' => 5, 'po_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
            // ['po_tanggal' => '2026-07-03', 'po_code' => 'PO-20260703-0001', 'po_id_supplier' => 3, 'po_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== DETAIL PO ==========
        DB::table('detail_po')->insert([
            ['po_detail_id_po' => 1, 'po_detail_id_product' => 1, 'po_detail_qty' => 2, 'po_detail_code' => 'POD-001', 'created_at' => $now, 'updated_at' => $now],
            ['po_detail_id_po' => 2, 'po_detail_id_product' => 1, 'po_detail_qty' => 3, 'po_detail_code' => 'POD-002', 'created_at' => $now, 'updated_at' => $now],
            // ['po_detail_id_po' => 3, 'po_detail_id_product' => 2, 'po_detail_qty' => 3, 'po_detail_code' => 'POD-003', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ========== STOCK ==========
        // stock_code mengikuti konvensi barcode = diawali product_code (PROD-xx),
        // konsisten dengan BarcodeController::postGenerate (implode product_code#timestamp#qty#exp).
        // stock_pallet_code mengikuti MasukRealisasi::generateGroupCode() = 'PAL-<Ymd>-<6digit>'
        // (sama seperti kode pallet yg tampil saat status READY di halaman realisasikan).
        // Satu pallet = gabungan total produk di lokasi yang sama (mis. PAL-20260701-000101 =
        // PROD-01 @ LOC-01, total 200+50=250).
        DB::table('stock')->insert([
            ['stock_code' => 'PROD-01#202608051842306A7321A632D4D#1#20270505', 'stock_pallet_code' => 'PAL-20260701-000101', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 10, 'stock_expired_date' => '2027-05-05', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
            ['stock_code' => 'PROD-01#202608051842306A7321A633B6A#1#20270105', 'stock_pallet_code' => 'PAL-20260701-000102', 'stock_id_product' => 1,  'stock_code_lokasi' => 'LOC-07', 'stock_qty' => 20, 'stock_expired_date' => '2027-01-05', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
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

        DB::table('so')->insert([
            ['so_tanggal' => '2026-07-01', 'so_code' => 'SO-20260701-0001', 'so_id_customer' => 1, 'so_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
            ['so_tanggal' => '2026-07-02', 'so_code' => 'SO-20260701-0002', 'so_id_customer' => 2, 'so_status' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('detail_so')->insert([
            ['so_detail_id_so' => 1, 'so_detail_id_product' => 1, 'so_detail_qty' => 2, 'so_detail_code' => 'SOD-001', 'created_at' => $now, 'updated_at' => $now],
            ['so_detail_id_so' => 2, 'so_detail_id_product' => 1, 'so_detail_qty' => 3, 'so_detail_code' => 'SOD-002', 'created_at' => $now, 'updated_at' => $now],
        ]);

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
