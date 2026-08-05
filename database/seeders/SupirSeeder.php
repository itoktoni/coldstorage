<?php

namespace Database\Seeders;

use App\Models\Supir;
use Illuminate\Database\Seeder;

class SupirSeeder extends Seeder
{
    public function run(): void
    {
        $supirs = [
            ['supir_nama' => 'Budi Santoso', 'supir_telp' => '081234567890'],
            ['supir_nama' => 'Andi Wijaya', 'supir_telp' => '081234567891'],
            ['supir_nama' => 'Dedi Kurniawan', 'supir_telp' => '081234567892'],
            ['supir_nama' => 'Eko Prasetyo', 'supir_telp' => '081234567893'],
            ['supir_nama' => 'Fajar Nugroho', 'supir_telp' => '081234567894'],
        ];

        foreach ($supirs as $s) {
            Supir::updateOrCreate(
                ['supir_nama' => $s['supir_nama']],
                $s
            );
        }
    }
}
