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
        Schema::create('split_target', function (Blueprint $table) {
            $table->id('split_target_id');
            $table->foreignId('split_target_id_split')->constrained('split', 'split_id');
            $table->foreignId('split_target_id_product')->constrained('product', 'product_id');
            $table->decimal('split_target_qty', 10, 2);
            $table->integer('split_target_jumlah')->default(1);
            $table->integer('split_target_urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_target');
    }
};
