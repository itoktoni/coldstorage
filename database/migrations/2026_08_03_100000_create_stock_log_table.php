<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_log', function (Blueprint $table) {
            $table->id();
            $table->string('stock_log_code', 50)->unique();
            $table->unsignedBigInteger('stock_id');
            $table->string('stock_code', 100)->nullable();
            $table->unsignedBigInteger('stock_id_product');
            $table->string('stock_code_lokasi', 50)->nullable();
            $table->string('stock_type', 20);
            $table->decimal('stock_qty', 15, 3);
            $table->decimal('stock_qty_before', 15, 3)->nullable();
            $table->decimal('stock_qty_after', 15, 3)->nullable();
            $table->string('action', 20); // CREATE, UPDATE, DELETE, CONSUME, RELEASE
            $table->text('description')->nullable();
            $table->string('stock_reff', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('stock_id')->references('stock_id')->on('stock')->cascadeOnDelete();
            $table->foreign('stock_id_product')->references('product_id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_log');
    }
};
