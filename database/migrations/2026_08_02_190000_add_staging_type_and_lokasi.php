<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock MODIFY stock_type ENUM('IN','OUT','RESERVE','STAGING') NOT NULL DEFAULT 'IN'");
        }

        $now = now();
        $gudang = DB::table('gudang')->first();

        foreach (['A', 'B', 'C', 'D'] as $letter) {
            $code = 'STG-' . $letter;
            DB::table('lokasi')->updateOrInsert(
                ['lokasi_code' => $code],
                [
                    'lokasi_nama'      => 'Staging Area ' . $letter,
                    'lokasi_code_gudang' => $gudang?->gudang_code ?? 'GD-01',
                    'lokasi_category'  => 'Staging',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock MODIFY stock_type ENUM('IN','OUT','RESERVE') NOT NULL DEFAULT 'IN'");
        }
        DB::table('lokasi')->whereIn('lokasi_code', ['STG-A', 'STG-B', 'STG-C', 'STG-D'])->delete();
    }
};
