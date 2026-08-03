<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('out_detail_id_so_detail')->nullable()->after('out_detail_id_product');
            $table->foreign('out_detail_id_so_detail')->references('so_detail_id')->on('detail_so')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->dropForeign(['out_detail_id_so_detail']);
            $table->dropColumn('out_detail_id_so_detail');
        });
    }
};
