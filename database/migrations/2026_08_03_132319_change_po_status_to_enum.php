<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('po')->where('po_status', 'pending')->update(['po_status' => 'Pending']);

        Schema::table('po', function (Blueprint $table) {
            $table->enum('po_status', ['Pending', 'Ordered', 'Partial', 'Closed'])
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->string('po_status', 30)->default('Pending')->change();
        });
    }
};
