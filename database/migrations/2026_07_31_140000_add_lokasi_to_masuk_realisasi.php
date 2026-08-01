<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->foreignId('in_realisasi_id_lokasi')->after('in_realisasi_qty')->constrained('lokasi', 'lokasi_id');
        });
    }

    public function down(): void
    {
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->dropForeign(['in_realisasi_id_lokasi']);
            $table->dropColumn('in_realisasi_id_lokasi');
        });
    }
};
