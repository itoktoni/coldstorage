<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FKs (idempotent — skip if already dropped)
        $this->dropFkIfExists('lokasi', 'lokasi_lokasi_id_gudang_foreign');
        $this->dropFkIfExists('stock', 'stock_stock_id_lokasi_foreign');
        $this->dropFkIfExists('masuk_realisasi', 'masuk_realisasi_in_realisasi_id_lokasi_foreign');

        // ---- GUDANG: gudang_id (bigint auto) -> gudang_code (string PK) ----
        if (!$this->hasColumn('gudang', 'gudang_code')) {
            DB::statement("ALTER TABLE gudang ADD COLUMN gudang_code VARCHAR(50) NULL AFTER gudang_id");
        }
        DB::statement("UPDATE gudang SET gudang_code = CAST(gudang_id AS CHAR) WHERE gudang_code IS NULL OR gudang_code = ''");
        if ($this->hasColumn('gudang', 'gudang_id')) {
            if ($this->hasPrimaryKey('gudang')) {
                DB::statement("ALTER TABLE gudang MODIFY gudang_id BIGINT UNSIGNED NOT NULL");
                DB::statement("ALTER TABLE gudang DROP PRIMARY KEY");
            }
            DB::statement("ALTER TABLE gudang DROP COLUMN gudang_id");
        }
        DB::statement("ALTER TABLE gudang MODIFY gudang_code VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE gudang ADD PRIMARY KEY (gudang_code)");

        // ---- LOKASI: lokasi_id (bigint auto) -> lokasi_code (string PK) ----
        if (!$this->hasColumn('lokasi', 'lokasi_code')) {
            DB::statement("ALTER TABLE lokasi ADD COLUMN lokasi_code VARCHAR(50) NULL AFTER lokasi_id");
        }
        DB::statement("UPDATE lokasi SET lokasi_code = CAST(lokasi_id AS CHAR) WHERE lokasi_code IS NULL OR lokasi_code = ''");
        if ($this->hasColumn('lokasi', 'lokasi_id')) {
            if ($this->hasPrimaryKey('lokasi')) {
                DB::statement("ALTER TABLE lokasi MODIFY lokasi_id BIGINT UNSIGNED NOT NULL");
                DB::statement("ALTER TABLE lokasi DROP PRIMARY KEY");
            }
            DB::statement("ALTER TABLE lokasi DROP COLUMN lokasi_id");
        }
        DB::statement("ALTER TABLE lokasi MODIFY lokasi_code VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE lokasi ADD PRIMARY KEY (lokasi_code)");

        // ---- LOKASI FK: lokasi_id_gudang -> lokasi_code_gudang ----
        if ($this->hasColumn('lokasi', 'lokasi_id_gudang')) {
            DB::statement("ALTER TABLE lokasi CHANGE lokasi_id_gudang lokasi_code_gudang VARCHAR(50) NOT NULL");
        }
        DB::statement("UPDATE lokasi SET lokasi_code_gudang = CAST(lokasi_code_gudang AS CHAR)");

        // ---- STOCK FK: stock_id_lokasi -> stock_code_lokasi ----
        if ($this->hasColumn('stock', 'stock_id_lokasi')) {
            DB::statement("ALTER TABLE stock CHANGE stock_id_lokasi stock_code_lokasi VARCHAR(50) NOT NULL");
        }
        DB::statement("UPDATE stock SET stock_code_lokasi = CAST(stock_code_lokasi AS CHAR)");

        // ---- MASUK_REALISASI FK: in_realisasi_id_lokasi -> in_realisasi_code_lokasi ----
        if ($this->hasColumn('masuk_realisasi', 'in_realisasi_id_lokasi')) {
            DB::statement("ALTER TABLE masuk_realisasi CHANGE in_realisasi_id_lokasi in_realisasi_code_lokasi VARCHAR(50) NOT NULL");
        }
        DB::statement("UPDATE masuk_realisasi SET in_realisasi_code_lokasi = CAST(in_realisasi_code_lokasi AS CHAR)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE masuk_realisasi CHANGE in_realisasi_code_lokasi in_realisasi_id_lokasi BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE stock CHANGE stock_code_lokasi stock_id_lokasi BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE lokasi CHANGE lokasi_code_gudang lokasi_id_gudang BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE lokasi DROP PRIMARY KEY");
        DB::statement("ALTER TABLE lokasi ADD COLUMN lokasi_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        DB::statement("ALTER TABLE gudang DROP PRIMARY KEY");
        DB::statement("ALTER TABLE gudang ADD COLUMN gudang_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }

    private function hasColumn(string $table, string $column): bool
    {
        return count(DB::select(
            "SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$table, $column]
        )) > 0;
    }

    private function hasPrimaryKey(string $table): bool
    {
        return count(DB::select(
            "SELECT constraint_name FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_type = 'PRIMARY KEY'",
            [$table]
        )) > 0;
    }

    private function dropFkIfExists(string $table, string $fkName): void
    {
        $rows = DB::select(
            "SELECT constraint_name FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
            [$table, $fkName]
        );
        if (count($rows) > 0) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
        }
    }
};
