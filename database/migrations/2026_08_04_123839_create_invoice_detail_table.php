<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_detail', function (Blueprint $table) {
            $table->id('invoice_detail_id');
            $table->unsignedBigInteger('invoice_detail_id_invoice');
            $table->unsignedBigInteger('invoice_detail_id_product');
            $table->decimal('invoice_detail_qty', 15, 3)->default(0);
            $table->decimal('invoice_detail_harga', 15, 2)->default(0);
            $table->decimal('invoice_detail_subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('invoice_detail_id_invoice')->references('invoice_id')->on('invoice')->onDelete('cascade');
            $table->foreign('invoice_detail_id_product')->references('product_id')->on('product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_detail');
    }
};
