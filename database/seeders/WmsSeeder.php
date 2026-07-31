<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsSeeder extends Seeder
{
    public function run(): void
    {
        // FK order: gudang → lokasi → product → stock → masuk_detail → masuk_realisasi → keluar → keluar_detail → keluar_realisasi → split

        DB::table('gudang')->insert([
            ['gudang_nama' => 'Gudang Utama', 'created_at' => now(), 'updated_at' => now()],
            ['gudang_nama' => 'Gudang Cabang', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('lokasi')->insert([
            ['lokasi_nama' => 'Rak A-1', 'lokasi_id_gudang' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['lokasi_nama' => 'Rak A-2', 'lokasi_id_gudang' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['lokasi_nama' => 'Rak B-1', 'lokasi_id_gudang' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('product')->insert([
            ['product_nama' => 'Beras 5kg', 'product_harga' => 65000, 'created_at' => now(), 'updated_at' => now()],
            ['product_nama' => 'Minyak Goreng 1L', 'product_harga' => 14000, 'created_at' => now(), 'updated_at' => now()],
            ['product_nama' => 'Gula Pasir 1kg', 'product_harga' => 15000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('stock')->insert([
            ['stock_code' => 'STK-001', 'stock_id_product' => 1, 'stock_id_lokasi' => 1, 'stock_qty' => 100, 'stock_expired_date' => '2027-01-01', 'stock_type' => 'IN', 'created_at' => now(), 'updated_at' => now()],
            ['stock_code' => 'STK-002', 'stock_id_product' => 2, 'stock_id_lokasi' => 1, 'stock_qty' => 50, 'stock_expired_date' => null, 'stock_type' => 'IN', 'created_at' => now(), 'updated_at' => now()],
            ['stock_code' => 'STK-003', 'stock_id_product' => 3, 'stock_id_lokasi' => 2, 'stock_qty' => 75, 'stock_expired_date' => '2027-06-01', 'stock_type' => 'IN', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('masuk_detail')->insert([
            ['in_detail_code' => 'IN-20260701-001', 'in_detail_tanggal' => '2026-07-01', 'in_detail_status' => 'Done', 'in_detail_id_product' => 1, 'in_detail_qty' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['in_detail_code' => 'IN-20260702-001', 'in_detail_tanggal' => '2026-07-02', 'in_detail_status' => 'Pending', 'in_detail_id_product' => 2, 'in_detail_qty' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('masuk_realisasi')->insert([
            ['in_realisasi_masuk_code' => 'IN-20260701-001', 'in_realisasi_code' => 'INR-001', 'in_realisasi_id_product' => 1, 'in_realisasi_qty' => 50, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('keluar')->insert([
            ['out_code' => 'OUT-20260703-001', 'out_tanggal' => '2026-07-03', 'out_status' => 'Pending', 'out_catatan' => 'Pengiriman ke toko A', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('keluar_detail')->insert([
            ['out_detail_code_keluar' => 'OUT-20260703-001', 'out_detail_id_product' => 1, 'out_detail_code' => 'OD-001', 'out_detail_qty' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('keluar_realisasi')->insert([
            ['out_realisasi_id_detail' => 1, 'out_realisasi_code' => 'OR-001', 'out_realisasi_id_stock' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('split')->insert([
            ['split_id_product' => 1, 'split_id_stock' => 1, 'split_qty_new' => 90, 'split_qty_old' => 100, 'split_qty_waste' => 0, 'split_tanggal' => '2026-07-01', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
