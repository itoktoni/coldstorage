<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po', function (Blueprint $table) {
            $table->id('po_id');
            $table->date('po_tanggal');
            $table->string('po_code', 50)->unique();
            $table->string('po_supplier', 200);
            $table->text('po_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_po', function (Blueprint $table) {
            $table->id('po_detail_id');
            $table->foreignId('po_detail_id_po')->constrained('po', 'po_id')->cascadeOnDelete();
            $table->foreignId('po_detail_id_product')->constrained('product', 'product_id');
            $table->integer('po_detail_qty');
            $table->string('po_detail_code', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_po');
        Schema::dropIfExists('po');
    }
};
