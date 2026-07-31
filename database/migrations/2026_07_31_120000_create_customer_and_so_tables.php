<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_nama', 200);
            $table->string('customer_telepon', 30)->nullable();
            $table->text('customer_alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('so', function (Blueprint $table) {
            $table->id('so_id');
            $table->date('so_tanggal');
            $table->string('so_code', 50)->unique();
            $table->foreignId('so_id_customer')->constrained('customer', 'customer_id');
            $table->string('so_status', 30)->default('Pending');
            $table->text('so_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_so', function (Blueprint $table) {
            $table->id('so_detail_id');
            $table->foreignId('so_detail_id_so')->constrained('so', 'so_id')->cascadeOnDelete();
            $table->foreignId('so_detail_id_product')->constrained('product', 'product_id');
            $table->integer('so_detail_qty');
            $table->decimal('so_detail_harga', 15, 2)->default(0);
            $table->string('so_detail_code', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_so');
        Schema::dropIfExists('so');
        Schema::dropIfExists('customer');
    }
};
