<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->string('po_status', 30)->default('Pending')->after('po_supplier');
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn('po_status');
        });
    }
};
