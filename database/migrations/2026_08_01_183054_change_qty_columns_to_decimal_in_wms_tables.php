<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->decimal('stock_qty', 10, 3)->default(0)->change();
        });
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->decimal('in_detail_qty', 10, 3)->change();
        });
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->decimal('in_realisasi_qty', 10, 3)->change();
        });
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->decimal('out_detail_qty', 10, 3)->change();
        });
        if (Schema::hasTable('so_detail')) {
            Schema::table('so_detail', function (Blueprint $table) {
                $table->decimal('so_detail_qty', 10, 3)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->integer('stock_qty')->default(0)->change();
        });
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->integer('in_detail_qty')->change();
        });
        Schema::table('masuk_realisasi', function (Blueprint $table) {
            $table->integer('in_realisasi_qty')->change();
        });
        Schema::table('keluar_detail', function (Blueprint $table) {
            $table->integer('out_detail_qty')->change();
        });
        if (Schema::hasTable('so_detail')) {
            Schema::table('so_detail', function (Blueprint $table) {
                $table->integer('so_detail_qty')->change();
            });
        }
    }
};
