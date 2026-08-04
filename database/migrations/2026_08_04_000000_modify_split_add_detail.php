<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split', function (Blueprint $table) {
            $table->dropForeign('split_split_id_product_foreign');
            $table->dropForeign('split_split_id_stock_foreign');
            $table->dropColumn(['split_id_product', 'split_id_stock', 'split_id_reff', 'split_qty_new', 'split_qty_old', 'split_qty_waste']);

            $table->foreignId('split_id_product_target')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('split_id_product_waste')->nullable()->constrained('product', 'product_id')->onDelete('set null');
            $table->double('split_qty_hasil')->default(0);
            $table->double('split_qty_waste')->default(0);
            $table->double('split_qty_penyusutan')->default(0);
            $table->string('split_status', 20)->default('Draft');
        });

        Schema::create('split_detail', function (Blueprint $table) {
            $table->id('split_detail_id');
            $table->foreignId('split_detail_id_split')->constrained('split', 'split_id')->onDelete('cascade');
            $table->foreignId('split_detail_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->double('split_detail_qty')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_detail');

        Schema::table('split', function (Blueprint $table) {
            $table->dropForeign('split_split_id_product_target_foreign');
            $table->dropForeign('split_split_id_product_waste_foreign');
            $table->dropColumn(['split_id_product_target', 'split_id_product_waste', 'split_qty_hasil', 'split_qty_waste', 'split_qty_penyusutan', 'split_status']);

            $table->foreignId('split_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('split_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->integer('split_id_reff')->nullable();
            $table->double('split_qty_new');
            $table->double('split_qty_old');
            $table->double('split_qty_waste');
        });
    }
};
