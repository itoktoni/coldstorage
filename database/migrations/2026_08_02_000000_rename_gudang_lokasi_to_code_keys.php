<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh installs (tests / sqlite): rebuild the 4 tables with the
        // code-based schema in one shot (tables are empty at migrate time).
        if (DB::getDriverName() !== 'mysql') {
            $this->rebuildTables();

            return;
        }

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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE masuk_realisasi CHANGE in_realisasi_code_lokasi in_realisasi_id_lokasi BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE stock CHANGE stock_code_lokasi stock_id_lokasi BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE lokasi CHANGE lokasi_code_gudang lokasi_id_gudang BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE lokasi DROP PRIMARY KEY");
        DB::statement("ALTER TABLE lokasi ADD COLUMN lokasi_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        DB::statement("ALTER TABLE gudang DROP PRIMARY KEY");
        DB::statement("ALTER TABLE gudang ADD COLUMN gudang_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }

    private function rebuildTables(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::dropIfExists('masuk_realisasi');
            Schema::dropIfExists('stock');
            Schema::dropIfExists('lokasi');
            Schema::dropIfExists('gudang');

            Schema::create('gudang', function (Blueprint $table) {
                $table->string('gudang_code', 50)->primary();
                $table->string('gudang_nama', 100)->unique();
                $table->timestamps();
            });

            Schema::create('lokasi', function (Blueprint $table) {
                $table->string('lokasi_code', 50)->primary();
                $table->string('lokasi_nama', 100);
                $table->string('lokasi_code_gudang', 50);
                $table->decimal('lokasi_max_qty', 10, 3)->nullable();
                $table->string('lokasi_category')->nullable();
                $table->timestamps();
            });

            Schema::create('stock', function (Blueprint $table) {
                $table->id('stock_id');
                $table->string('stock_code', 50)->unique();
                $table->foreignId('stock_id_product')->constrained('product', 'product_id')->onDelete('cascade');
                $table->string('stock_code_lokasi', 50)->nullable();
                $table->decimal('stock_qty', 10, 3)->default(0);
                $table->date('stock_expired_date')->nullable();
                $table->string('stock_reff', 100)->nullable();
                $table->string('stock_type', 20)->default('IN');
                $table->timestamps();
            });

            Schema::create('masuk_realisasi', function (Blueprint $table) {
                $table->id('in_realisasi_id');
                $table->string('in_realisasi_masuk_code', 50);
                $table->foreign('in_realisasi_masuk_code')->references('in_detail_code')->on('masuk_detail')->onDelete('cascade');
                $table->string('in_realisasi_code', 50)->unique();
                $table->foreignId('in_realisasi_id_product')->constrained('product', 'product_id')->onDelete('cascade');
                $table->decimal('in_realisasi_qty', 10, 3);
                $table->string('in_realisasi_code_lokasi', 50);
                $table->string('in_realisasi_group', 50)->nullable();
                $table->string('in_realisasi_barcode')->nullable();
                $table->timestamps();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
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
