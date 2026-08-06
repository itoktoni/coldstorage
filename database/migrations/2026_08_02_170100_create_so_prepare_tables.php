<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('so_prepare', function (Blueprint $table) {
            $table->id('so_prepare_id');
            $table->foreignId('so_prepare_id_so')->constrained('so', 'so_id')->cascadeOnDelete();
            $table->string('so_prepare_code', 50)->unique();
            $table->string('so_prepare_id_keluar', 50)->nullable();
            $table->enum('so_prepare_status', ['Pending', 'Done'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('so_prepare_detail', function (Blueprint $table) {
            $table->id('so_prepare_detail_id');
            $table->foreignId('so_prepare_detail_id_prepare')->constrained('so_prepare', 'so_prepare_id')->cascadeOnDelete();
            $table->foreignId('so_prepare_detail_id_realisasi')->constrained('keluar_realisasi', 'out_realisasi_id')->cascadeOnDelete();
            $table->foreignId('so_prepare_detail_id_product')->constrained('product', 'product_id');
            $table->decimal('so_prepare_detail_qty', 10, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('so_prepare_detail');
        Schema::dropIfExists('so_prepare');
    }
};
