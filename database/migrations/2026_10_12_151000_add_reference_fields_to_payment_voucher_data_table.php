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
        Schema::table('payment_voucher_data', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_voucher_data', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('description');
            }
            if (!Schema::hasColumn('payment_voucher_data', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('payment_voucher_data', 'customer_advance_id')) {
                $table->unsignedBigInteger('customer_advance_id')->nullable()->after('reference_id');
                $table->foreign('customer_advance_id')->references('id')->on('customer_advances')->onDelete('set null');
            }
            if (!Schema::hasColumn('payment_voucher_data', 'sales_order_id')) {
                $table->unsignedBigInteger('sales_order_id')->nullable()->after('customer_advance_id');
                $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
            }
            if (!Schema::hasColumn('payment_voucher_data', 'delivery_order_id')) {
                $table->unsignedBigInteger('delivery_order_id')->nullable()->after('sales_order_id');
                $table->foreign('delivery_order_id')->references('id')->on('delivery_order')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_voucher_data', function (Blueprint $table) {
            if (Schema::hasColumn('payment_voucher_data', 'delivery_order_id')) {
                $table->dropForeign(['delivery_order_id']);
                $table->dropColumn('delivery_order_id');
            }
            if (Schema::hasColumn('payment_voucher_data', 'sales_order_id')) {
                $table->dropForeign(['sales_order_id']);
                $table->dropColumn('sales_order_id');
            }
            if (Schema::hasColumn('payment_voucher_data', 'customer_advance_id')) {
                $table->dropForeign(['customer_advance_id']);
                $table->dropColumn('customer_advance_id');
            }
            if (Schema::hasColumn('payment_voucher_data', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('payment_voucher_data', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
        });
    }
};
