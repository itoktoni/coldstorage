<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split', function (Blueprint $table) {
            $table->foreignId('split_id_product_source')->nullable()->after('split_id')->constrained('product', 'product_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('split', function (Blueprint $table) {
            $table->dropForeign(['split_id_product_source']);
            $table->dropColumn('split_id_product_source');
        });
    }
};
