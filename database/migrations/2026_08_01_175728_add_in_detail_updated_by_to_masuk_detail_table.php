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
        if (!Schema::hasColumn('masuk_detail', 'in_detail_updated_by')) {
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->unsignedInteger('in_detail_updated_by')->nullable()->after('in_detail_created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('masuk_detail', 'in_detail_updated_by')) {
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->dropColumn('in_detail_updated_by');
            });
        }
    }
};
