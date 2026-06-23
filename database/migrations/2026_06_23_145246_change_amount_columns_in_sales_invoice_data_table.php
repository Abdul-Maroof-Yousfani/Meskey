<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_invoice_data', function (Blueprint $table) {
            DB::statement('ALTER TABLE sales_invoice_data MODIFY packing DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY no_of_bags DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY qty DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY rate DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY gross_amount DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY discount_percent DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY discount_amount DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY amount DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY gst_percent DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY gst_amount DOUBLE DEFAULT 0');
            DB::statement('ALTER TABLE sales_invoice_data MODIFY net_amount DOUBLE DEFAULT 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_data', function (Blueprint $table) {
            //
        });
    }
};
