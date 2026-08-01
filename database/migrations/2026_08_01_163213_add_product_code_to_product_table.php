<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasColumn = Schema::hasColumn('product', 'product_code');

        if (!$hasColumn) {
            Schema::table('product', function (Blueprint $table) {
                $table->string('product_code', 11)->nullable()->after('product_id');
            });
        }

        // Backfill existing products with generated codes using raw SQL
        DB::unprepared("
            UPDATE product
            SET product_code = CONCAT('P', LPAD(product_id, 10, '0'))
            WHERE product_code = '' OR product_code IS NULL
        ");

        // Add unique constraint if not exists
        $hasUnique = DB::select("SHOW INDEX FROM product WHERE Column_name = 'product_code' AND Key_name != 'PRIMARY'");
        if (empty($hasUnique)) {
            Schema::table('product', function (Blueprint $table) {
                $table->unique('product_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('product_code');
        });
    }
};
