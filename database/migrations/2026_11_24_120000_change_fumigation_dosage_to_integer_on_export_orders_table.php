<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * export_orders.fumigation_dosage: VARCHAR(100) -> INT UNSIGNED NULL
 *
 * Pehle jo values sirf number nahi hain (khali, "20 g", "abc" waghera) unhe NULL kar diya jata hai,
 * warna column type badalte waqt MySQL error deta hai.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('export_orders', 'fumigation_dosage')) {
            return;
        }

        DB::statement("UPDATE export_orders SET fumigation_dosage = TRIM(fumigation_dosage) WHERE fumigation_dosage IS NOT NULL");
        DB::statement("UPDATE export_orders SET fumigation_dosage = NULL WHERE fumigation_dosage IS NOT NULL AND fumigation_dosage NOT REGEXP '^[0-9]+$'");
        DB::statement('ALTER TABLE export_orders MODIFY fumigation_dosage INT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('export_orders', 'fumigation_dosage')) {
            return;
        }

        DB::statement('ALTER TABLE export_orders MODIFY fumigation_dosage VARCHAR(100) NULL');
    }
};
