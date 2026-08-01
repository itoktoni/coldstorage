<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First change column type from enum to varchar
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->string('in_detail_status', 20)
                ->default('pending')
                ->comment('pending, process, ready, complete')
                ->change();
        });

        // Then migrate existing data
        DB::table('masuk_detail')
            ->where('in_detail_status', 'Pending')
            ->update(['in_detail_status' => 'pending']);
        DB::table('masuk_detail')
            ->where('in_detail_status', 'In Progress')
            ->update(['in_detail_status' => 'process']);
        DB::table('masuk_detail')
            ->where('in_detail_status', 'Done')
            ->update(['in_detail_status' => 'complete']);
    }

    public function down(): void
    {
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->enum('in_detail_status', ['Pending', 'In Progress', 'Done'])
                ->default('Pending')
                ->change();
        });
    }
};
