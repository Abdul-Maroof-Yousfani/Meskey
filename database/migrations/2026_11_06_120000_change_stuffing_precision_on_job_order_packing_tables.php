<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Export Order stores stuffing with 3 decimals; Job Order / Request must accept the same.
    private array $tables = ['job_order_packing_items', 'job_order_request_packing_items'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'stuffing_in_container')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `stuffing_in_container` DECIMAL(10,3) NOT NULL DEFAULT 0");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'stuffing_in_container')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `stuffing_in_container` DECIMAL(8,2) NOT NULL DEFAULT 0");
            }
        }
    }
};
