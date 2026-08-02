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
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->string('out_detail_reff', 255)->nullable()->after('out_detail_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->dropColumn('out_detail_reff');
        });
    }
};
