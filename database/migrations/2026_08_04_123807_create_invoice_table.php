<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('invoice_code')->unique();
            $table->date('invoice_tanggal');
            $table->unsignedBigInteger('invoice_id_so');
            $table->unsignedBigInteger('invoice_id_customer');
            $table->decimal('invoice_subtotal', 15, 2)->default(0);
            $table->decimal('invoice_ppn', 15, 2)->default(0);
            $table->decimal('invoice_total', 15, 2)->default(0);
            $table->string('invoice_status')->default('Unpaid');
            $table->text('invoice_keterangan')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id_so')->references('so_id')->on('so')->onDelete('cascade');
            $table->foreign('invoice_id_customer')->references('customer_id')->on('customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
