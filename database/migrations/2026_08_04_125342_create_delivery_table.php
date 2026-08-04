<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery', function (Blueprint $table) {
            $table->id('delivery_id');
            $table->string('delivery_code')->unique();
            $table->date('delivery_tanggal');
            $table->unsignedBigInteger('delivery_id_so');
            $table->unsignedBigInteger('delivery_id_invoice')->nullable();
            $table->string('delivery_nama_penerima')->nullable();
            $table->text('delivery_alamat_tujuan')->nullable();
            $table->string('delivery_nama_driver')->nullable();
            $table->string('delivery_plat_kendaraan')->nullable();
            $table->string('delivery_nama_kurir')->nullable();
            $table->text('delivery_catatan')->nullable();
            $table->string('delivery_status')->default('Pending');
            $table->timestamps();

            $table->foreign('delivery_id_so')->references('so_id')->on('so')->onDelete('cascade');
            $table->foreign('delivery_id_invoice')->references('invoice_id')->on('invoice')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery');
    }
};
