<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['bill_of_ladings', 'commercial_invoices', 'packing_lists'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'am_approval_status')) {
                    $t->string('am_approval_status')->default('pending');
                }
                if (!Schema::hasColumn($table, 'am_change_made')) {
                    $t->boolean('am_change_made')->default(0);
                }
                
                // Add remarks only for commercial_invoices and packing_lists
                if (in_array($table, ['commercial_invoices', 'packing_lists'])) {
                    if (!Schema::hasColumn($table, 'remarks')) {
                        $t->text('remarks')->nullable();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['bill_of_ladings', 'commercial_invoices', 'packing_lists'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $columns = ['am_approval_status', 'am_change_made'];
                if (in_array($table, ['commercial_invoices', 'packing_lists'])) {
                    $columns[] = 'remarks';
                }
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
