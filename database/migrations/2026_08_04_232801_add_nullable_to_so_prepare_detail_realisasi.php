<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('so_prepare_detail', function (Blueprint $table) {
            // Allows a scanned stock (IN/STAGING) to be allocated to an SO even
            // when no matching KeluarDetail/KeluarRealisasi exists yet (e.g. stock
            // moved directly to staging by the forklift flow).
            $table->dropForeign(['so_prepare_detail_id_realisasi']);
            $table->unsignedBigInteger('so_prepare_detail_id_realisasi')->nullable()->change();
            $table->foreign('so_prepare_detail_id_realisasi')
                ->references('out_realisasi_id')
                ->on('keluar_realisasi')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_prepare_detail', function (Blueprint $table) {
            $table->dropForeign(['so_prepare_detail_id_realisasi']);
            $table->unsignedBigInteger('so_prepare_detail_id_realisasi')->nullable(false)->change();
            $table->foreign('so_prepare_detail_id_realisasi')
                ->references('out_realisasi_id')
                ->on('keluar_realisasi')
                ->cascadeOnDelete();
        });
    }
};
