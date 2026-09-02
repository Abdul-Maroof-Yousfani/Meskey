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
        $dbName = DB::getDatabaseName();

        // 1. Drop foreign key constraint on sale_return_sale_invoice table
        if (Schema::hasTable('sale_return_sale_invoice')) {
            $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $dbName)
                ->where('TABLE_NAME', 'sale_return_sale_invoice')
                ->where('CONSTRAINT_NAME', 'sale_return_sale_invoice_sale_invoice_id_foreign')
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();

            if ($fkExists) {
                Schema::table('sale_return_sale_invoice', function (Blueprint $table) {
                    $table->dropForeign('sale_return_sale_invoice_sale_invoice_id_foreign');
                });
            }
        }

        // 2. Safely drop foreign key constraint on sale_return_data table if it exists
        if (Schema::hasTable('sale_return_data')) {
            $dataFkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $dbName)
                ->where('TABLE_NAME', 'sale_return_data')
                ->where('CONSTRAINT_NAME', 'sale_return_data_sale_invoice_data_id_foreign')
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();

            if ($dataFkExists) {
                Schema::table('sale_return_data', function (Blueprint $table) {
                    $table->dropForeign('sale_return_data_sale_invoice_data_id_foreign');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sale_return_sale_invoice')) {
            Schema::table('sale_return_sale_invoice', function (Blueprint $table) {
                try {
                    $table->foreign('sale_invoice_id')
                        ->references('id')
                        ->on('sales_invoices')
                        ->cascadeOnDelete();
                } catch (\Throwable $e) {
                    // Ignore rollback exception if sales invoices schema differs
                }
            });
        }
    }
};
