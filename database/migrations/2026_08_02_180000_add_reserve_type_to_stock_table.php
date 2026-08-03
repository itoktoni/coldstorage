<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE stock MODIFY stock_type ENUM('IN','OUT','RESERVE') NOT NULL DEFAULT 'IN'");
        DB::statement("ALTER TABLE stock MODIFY stock_code_lokasi VARCHAR(50) NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE stock MODIFY stock_type ENUM('IN','OUT') NOT NULL DEFAULT 'IN'");
        DB::statement("ALTER TABLE stock MODIFY stock_code_lokasi VARCHAR(50) NOT NULL");
    }
};
