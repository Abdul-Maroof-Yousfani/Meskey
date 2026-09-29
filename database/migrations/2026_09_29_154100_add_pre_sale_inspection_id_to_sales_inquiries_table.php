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
        Schema::table('sales_inquiries', function (Blueprint $table) {
            $table->foreignId('pre_sale_inspection_id')->nullable()->after('inquiry_no')->constrained('pre_sale_inspections')->nullOnDelete();
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('pre_sale_inspection_id')->nullable()->after('inquiry_id')->constrained('pre_sale_inspections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_inquiries', function (Blueprint $table) {
            $table->dropForeign(['pre_sale_inspection_id']);
            $table->dropColumn('pre_sale_inspection_id');
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['pre_sale_inspection_id']);
            $table->dropColumn('pre_sale_inspection_id');
        });
    }
};
