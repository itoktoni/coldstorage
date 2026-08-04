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
        Schema::create('forklift_task', function (Blueprint $table) {
            $table->id('forklift_id');
            $table->enum('forklift_type', ['putaway', 'pick']);
            $table->string('forklift_pallet_code', 50);
            $table->string('forklift_lokasi_asal', 50)->nullable();
            $table->string('forklift_lokasi_tujuan', 50)->nullable();
            $table->string('forklift_lokasi_final', 50)->nullable();
            $table->string('forklift_reff', 100)->nullable();
            $table->enum('forklift_status', ['Pending', 'Progress', 'Done'])->default('Pending');
            $table->string('forklift_operator', 100)->nullable();
            $table->timestamp('forklift_scan_asal_at')->nullable();
            $table->timestamp('forklift_scan_tujuan_at')->nullable();
            $table->timestamps();

            $table->index('forklift_status');
            $table->index('forklift_type');
            $table->index('forklift_pallet_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forklift_task');
    }
};
