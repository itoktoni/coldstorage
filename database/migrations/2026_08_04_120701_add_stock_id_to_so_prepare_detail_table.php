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
            $table->unsignedBigInteger('so_prepare_detail_id_stock')->nullable()
                ->after('so_prepare_detail_id_product')
                ->comment('Stock row dari staging yang di-allocate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_prepare_detail', function (Blueprint $table) {
            $table->dropColumn('so_prepare_detail_id_stock');
        });
    }
};
