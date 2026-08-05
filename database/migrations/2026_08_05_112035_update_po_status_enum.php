<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE po MODIFY po_status ENUM('Pending','Process','Ready','Done') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE po MODIFY po_status ENUM('Pending','Ordered','Partial','Closed') NOT NULL DEFAULT 'Pending'");
    }
};
