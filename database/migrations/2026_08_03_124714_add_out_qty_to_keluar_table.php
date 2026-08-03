<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keluar', function (Blueprint $table) {
            $table->decimal('out_qty', 10, 3)->default(0)->after('out_status');
        });
    }

    public function down(): void
    {
        Schema::table('keluar', function (Blueprint $table) {
            $table->dropColumn('out_qty');
        });
    }
};
