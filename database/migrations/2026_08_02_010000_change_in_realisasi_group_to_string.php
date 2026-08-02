<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE masuk_realisasi MODIFY in_realisasi_group VARCHAR(50) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE masuk_realisasi MODIFY in_realisasi_group INT NULL");
    }
};
