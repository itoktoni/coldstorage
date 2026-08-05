<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery', function (Blueprint $table) {
            $table->foreignId('delivery_id_kendaraan')->nullable()->after('delivery_nama_driver');
            $table->foreignId('delivery_id_supir')->nullable()->after('delivery_id_kendaraan');
        });
    }

    public function down(): void
    {
        Schema::table('delivery', function (Blueprint $table) {
            $table->dropForeign(['delivery_id_kendaraan']);
            $table->dropForeign(['delivery_id_supir']);
            $table->dropColumn(['delivery_id_kendaraan', 'delivery_id_supir']);
        });
    }
};
