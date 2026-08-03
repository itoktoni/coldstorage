<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $products = [
            ['product_nama' => 'Daging Sapi Qurban (kg)', 'product_harga' => 135000],
            ['product_nama' => 'Daging Sapi Has Dalam (kg)', 'product_harga' => 180000],
            ['product_nama' => 'Daging Sapi Tetelan (kg)', 'product_harga' => 85000],
            ['product_nama' => 'Daging Kambing (kg)', 'product_harga' => 150000],
            ['product_nama' => 'Ayam Utuh Frozen (kg)', 'product_harga' => 38000],
            ['product_nama' => 'Dada Ayam Fillet (kg)', 'product_harga' => 62000],
            ['product_nama' => 'Paha Ayam (kg)', 'product_harga' => 42000],
            ['product_nama' => 'Sayap Ayam (kg)', 'product_harga' => 35000],
            ['product_nama' => 'Ikan Salmon Fillet (kg)', 'product_harga' => 220000],
            ['product_nama' => 'Ikan Kakap (kg)', 'product_harga' => 75000],
            ['product_nama' => 'Udang Vannamei (kg)', 'product_harga' => 120000],
            ['product_nama' => 'Cumi-cumi (kg)', 'product_harga' => 95000],
            ['product_nama' => 'Ikan Tongkol (kg)', 'product_harga' => 45000],
            ['product_nama' => 'Kentang Import (kg)', 'product_harga' => 28000],
            ['product_nama' => 'Wortel (kg)', 'product_harga' => 18000],
            ['product_nama' => 'Bawang Bombai (kg)', 'product_harga' => 22000],
            ['product_nama' => 'Susu UHT 1L', 'product_harga' => 16000],
            ['product_nama' => 'Keju Cheddar (kg)', 'product_harga' => 120000],
            ['product_nama' => 'Mentega Unsalted (kg)', 'product_harga' => 95000],
            ['product_nama' => 'Yoghurt Plain 1L', 'product_harga' => 28000],
            ['product_nama' => 'Krim Kental Manis (kg)', 'product_harga' => 45000],
            ['product_nama' => 'Keju Mozarella (kg)', 'product_harga' => 110000],
        ];

        foreach ($products as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        DB::table('product')->insert($products);

        $this->command->info(count($products) . ' product berhasil di-seed.');
    }
}
