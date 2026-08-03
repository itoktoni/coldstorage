<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keluar', function (Blueprint $table) {
            $table->boolean('out_assigned')->default(false)->after('out_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('keluar', function (Blueprint $table) {
            $table->dropColumn('out_assigned');
        });
    }
};
