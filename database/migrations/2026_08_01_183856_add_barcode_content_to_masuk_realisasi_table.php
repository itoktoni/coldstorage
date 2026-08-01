<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->text('in_realisasi_barcode')->after('in_realisasi_code');
        });
    }

    public function down(): void
    {
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->dropColumn('in_realisasi_barcode');
        });
    }
};
