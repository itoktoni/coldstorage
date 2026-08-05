<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $kendaraans = [
            ['kendaraan_nama' => 'Box Truck 6 Roda', 'kendaraan_plat' => 'B 1234 CD', 'kendaraan_tipe' => 'Box Truck'],
            ['kendaraan_nama' => 'Box Truck 4 Roda', 'kendaraan_plat' => 'B 5678 EF', 'kendaraan_tipe' => 'Box Truck'],
            ['kendaraan_nama' => 'Pick Up', 'kendaraan_plat' => 'B 9012 GH', 'kendaraan_tipe' => 'Pick Up'],
            ['kendaraan_nama' => 'Engkel', 'kendaraan_plat' => 'B 3456 IJ', 'kendaraan_tipe' => 'Engkel'],
            ['kendaraan_nama' => 'Tronton', 'kendaraan_plat' => 'B 7890 KL', 'kendaraan_tipe' => 'Tronton'],
        ];

        foreach ($kendaraans as $k) {
            Kendaraan::updateOrCreate(
                ['kendaraan_plat' => $k['kendaraan_plat']],
                $k
            );
        }
    }
}
