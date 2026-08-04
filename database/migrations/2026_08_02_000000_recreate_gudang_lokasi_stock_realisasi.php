<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure product_code exists
        if (!Schema::hasColumn('product', 'product_code')) {
            Schema::table('product', function (Blueprint $table) {
                $table->string('product_code', 11)->nullable()->unique()->after('product_id');
            });
        }

        // Ensure product_category exists
        if (!Schema::hasColumn('product', 'product_category')) {
            Schema::table('product', function (Blueprint $table) {
                $table->string('product_category', 50)->nullable()->after('product_nama');
            });
        }

        // masuk_detail status — drop old ENUM/CHECK constraint
        try {
            DB::statement("ALTER TABLE masuk_detail MODIFY in_detail_status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Handle SQLite differently
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->string('in_detail_status', 20)->default('pending')->change();
            });
        }
        if (!Schema::hasColumn('masuk_detail', 'in_detail_id_lokasi')) {
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->string('in_detail_id_lokasi', 50)->nullable()->after('in_detail_id_product');
            });
        }
        if (!Schema::hasColumn('masuk_detail', 'in_detail_updated_by')) {
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->unsignedInteger('in_detail_updated_by')->nullable()->after('in_detail_created_by');
            });
        }
        if (!Schema::hasColumn('masuk_detail', 'in_detail_id_staging')) {
            Schema::table('masuk_detail', function (Blueprint $table) {
                $table->string('in_detail_id_staging', 50)->nullable()->after('in_detail_id_lokasi');
            });
        }

        // Drop + recreate gudang, lokasi, stock, masuk_realisasi with new schema
        Schema::dropIfExists('masuk_realisasi');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('lokasi');
        Schema::dropIfExists('gudang');

        Schema::create('gudang', function (Blueprint $table) {
            $table->string('gudang_code', 50)->primary();
            $table->string('gudang_nama', 100);
            $table->timestamps();
            $table->unique('gudang_nama');
        });

        Schema::create('lokasi', function (Blueprint $table) {
            $table->string('lokasi_code', 50)->primary();
            $table->string('lokasi_nama', 100);
            $table->string('lokasi_code_gudang', 50);
            $table->decimal('lokasi_max_qty', 10, 3)->nullable();
            $table->string('lokasi_category', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('stock', function (Blueprint $table) {
            $table->id('stock_id');
            $table->string('stock_code', 50)->unique();
            $table->string('stock_pallet_code', 100)->nullable();
            $table->unsignedBigInteger('stock_id_product');
            $table->string('stock_code_lokasi', 50)->nullable();
            $table->decimal('stock_qty', 15, 3)->default(0);
            $table->date('stock_expired_date')->nullable();
            $table->string('stock_reff', 100)->nullable();
            $table->string('stock_type', 20)->default('IN');
            $table->timestamps();
            $table->foreign('stock_id_product')->references('product_id')->on('product')->onDelete('cascade');
        });

        Schema::create('masuk_realisasi', function (Blueprint $table) {
            $table->id('in_realisasi_id');
            $table->string('in_realisasi_masuk_code', 50);
            $table->string('in_realisasi_code', 50)->unique();
            $table->unsignedBigInteger('in_realisasi_id_product');
            $table->decimal('in_realisasi_qty', 10, 3);
            $table->string('in_realisasi_code_lokasi', 50);
            $table->string('in_realisasi_group', 50)->nullable();
            $table->string('in_realisasi_barcode', 255)->nullable();
            $table->timestamps();
            $table->foreign('in_realisasi_masuk_code')->references('in_detail_code')->on('masuk_detail')->onDelete('cascade');
            $table->foreign('in_realisasi_id_product')->references('product_id')->on('product')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masuk_realisasi');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('lokasi');
        Schema::dropIfExists('gudang');
    }
};