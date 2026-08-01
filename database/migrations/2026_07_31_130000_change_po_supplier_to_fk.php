<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->primary('supplier_id');
        });

        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn('po_supplier');
            $table->integer('po_id_supplier')->after('po_code');
            $table->foreign('po_id_supplier')->references('supplier_id')->on('supplier');
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropForeign(['po_id_supplier']);
            $table->dropColumn('po_id_supplier');
            $table->string('po_supplier', 200)->after('po_code');
        });

        Schema::table('supplier', function (Blueprint $table) {
            $table->dropPrimary(['supplier_id']);
        });
    }
};
