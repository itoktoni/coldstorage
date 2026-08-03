<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('so')->where('so_status', 'pending')->update(['so_status' => 'Pending']);

        Schema::table('so', function (Blueprint $table) {
            $table->enum('so_status', ['Pending', 'Prepare', 'Confirmed', 'Shipped', 'Closed'])
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('so', function (Blueprint $table) {
            $table->string('so_status', 30)->default('Pending')->change();
        });
    }
};
