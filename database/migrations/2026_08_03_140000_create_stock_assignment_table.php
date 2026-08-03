<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_assignment', function (Blueprint $table) {
            $table->id('stock_assignment_id');
            $table->string('stock_assignment_id_keluar', 255);
            $table->unsignedBigInteger('stock_assignment_id_stock');
            $table->unsignedBigInteger('stock_assignment_id_keluar_detail');
            $table->unsignedBigInteger('stock_assignment_id_so_detail');
            $table->decimal('stock_assignment_qty', 15, 3);
            $table->enum('stock_assignment_status', ['Pending', 'Picked', 'Override'])->default('Pending');
            $table->text('stock_assignment_notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_assignment_id_keluar')
                ->references('out_code')->on('keluar')->onDelete('cascade');
            $table->foreign('stock_assignment_id_stock')
                ->references('stock_id')->on('stock')->onDelete('cascade');
            $table->foreign('stock_assignment_id_keluar_detail')
                ->references('out_detail_id')->on('keluar_detail')->onDelete('cascade');
            $table->foreign('stock_assignment_id_so_detail')
                ->references('so_detail_id')->on('detail_so')->onDelete('cascade');

            $table->index(['stock_assignment_id_keluar']);
            $table->index(['stock_assignment_id_stock']);
            $table->index(['stock_assignment_id_keluar_detail']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_assignment');
    }
};
