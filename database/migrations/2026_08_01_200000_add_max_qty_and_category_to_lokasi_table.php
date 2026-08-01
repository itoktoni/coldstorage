<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lokasi', function (Blueprint $table) {
            $table->decimal('lokasi_max_qty', 10, 3)->nullable()->after('lokasi_nama')->comment('Kapasitas maksimal (null = tanpa batas)');
            $table->string('lokasi_category', 50)->nullable()->after('lokasi_max_qty')->comment('Kategori产品 (null = boleh apa saja)');
        });
    }

    public function down(): void
    {
        Schema::table('lokasi', function (Blueprint $table) {
            $table->dropColumn(['lokasi_max_qty', 'lokasi_category']);
        });
    }
};
