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
        $con = DB::connection()->getPdo();
        $db = DB::connection()->getDatabaseName();

        // Get the actual constraint name
        $stmt = $con->prepare("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'split_target' AND COLUMN_NAME = 'split_target_id_split' AND REFERENCED_TABLE_NAME = 'split'");
        $stmt->execute([$db]);
        $constraint = $stmt->fetch();

        if ($constraint) {
            DB::statement("ALTER TABLE `split_target` DROP FOREIGN KEY `{$constraint['CONSTRAINT_NAME']}`");
            DB::statement("ALTER TABLE `split_target` ADD CONSTRAINT `split_target_split_target_id_split_foreign` FOREIGN KEY (`split_target_id_split`) REFERENCES `split` (`split_id`) ON DELETE CASCADE");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `split_target` DROP FOREIGN KEY `split_target_split_target_id_split_foreign`");
        DB::statement("ALTER TABLE `split_target` ADD CONSTRAINT `split_target_split_target_id_split_foreign` FOREIGN KEY (`split_target_id_split`) REFERENCES `split` (`split_id`)");
    }
};
