<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang', function (Blueprint $table) {
            $table->id('gudang_id');
            $table->string('gudang_nama', 100)->unique();
            $table->timestamps();
        });

        Schema::create('lokasi', function (Blueprint $table) {
            $table->id('lokasi_id');
            $table->string('lokasi_nama', 100);
            $table->foreignId('lokasi_id_gudang')->constrained('gudang', 'gudang_id')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('product', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_nama', 200);
            $table->decimal('product_harga', 15, 2);
            $table->timestamps();
        });

        Schema::create('stock', function (Blueprint $table) {
            $table->id('stock_id');
            $table->string('stock_code', 50)->unique();
            $table->foreignId('stock_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('stock_id_lokasi')->constrained('lokasi', 'lokasi_id')->onDelete('cascade');
            $table->integer('stock_qty')->default(0);
            $table->date('stock_expired_date')->nullable();
            $table->string('stock_reff', 100)->nullable();
            $table->enum('stock_type', ['IN', 'OUT'])->default('IN');
            $table->timestamps();
        });

        Schema::create('masuk_detail', function (Blueprint $table) {
            $table->string('in_detail_code', 50)->primary();
            $table->string('in_detail_reff', 100)->nullable();
            $table->date('in_detail_tanggal');
            $table->enum('in_detail_status', ['Pending', 'In Progress', 'Done'])->default('Pending');
            $table->text('in_detail_catatan')->nullable();
            $table->timestamp('in_detail_created_at')->nullable();
            $table->unsignedInteger('in_detail_created_by')->nullable();
            $table->foreignId('in_detail_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->integer('in_detail_qty');
            $table->timestamps();
        });

        Schema::create('masuk_realisasi', function (Blueprint $table) {
            $table->id('in_realisasi_id');
            $table->string('in_realisasi_masuk_code', 50);
            $table->foreign('in_realisasi_masuk_code')->references('in_detail_code')->on('masuk_detail')->onDelete('cascade');
            $table->string('in_realisasi_code', 50)->unique();
            $table->foreignId('in_realisasi_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->integer('in_realisasi_qty');
            $table->integer('in_realisasi_group')->nullable();
            $table->timestamps();
        });

        Schema::create('keluar', function (Blueprint $table) {
            $table->string('out_code', 50)->primary();
            $table->string('out_reff', 100)->nullable();
            $table->date('out_tanggal');
            $table->enum('out_status', ['Pending', 'In Progress', 'Done'])->default('Pending');
            $table->text('out_catatan')->nullable();
            $table->timestamp('out_created_at')->nullable();
            $table->unsignedInteger('out_created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('keluar_detail', function (Blueprint $table) {
            $table->id('out_detail_id');
            $table->string('out_detail_code_keluar', 50);
            $table->foreign('out_detail_code_keluar')->references('out_code')->on('keluar')->onDelete('cascade');
            $table->foreignId('out_detail_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->string('out_detail_code', 50)->unique();
            $table->integer('out_detail_qty');
            $table->timestamps();
        });

        Schema::create('keluar_realisasi', function (Blueprint $table) {
            $table->id('out_realisasi_id');
            $table->foreignId('out_realisasi_id_detail')->constrained('keluar_detail', 'out_detail_id')->onDelete('cascade');
            $table->string('out_realisasi_code', 50)->unique();
            $table->foreignId('out_realisasi_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('split', function (Blueprint $table) {
            $table->id('split_id');
            $table->foreignId('split_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('split_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->integer('split_id_reff')->nullable();
            $table->double('split_qty_new');
            $table->double('split_qty_old');
            $table->double('split_qty_waste');
            $table->date('split_tanggal');
            $table->unsignedInteger('split_created_by')->nullable();
            $table->timestamp('split_created_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split');
        Schema::dropIfExists('keluar_realisasi');
        Schema::dropIfExists('keluar_detail');
        Schema::dropIfExists('keluar');
        Schema::dropIfExists('masuk_realisasi');
        Schema::dropIfExists('masuk_detail');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('product');
        Schema::dropIfExists('lokasi');
        Schema::dropIfExists('gudang');
    }
};
