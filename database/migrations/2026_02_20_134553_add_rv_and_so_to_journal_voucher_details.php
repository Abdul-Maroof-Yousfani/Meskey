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
        Schema::table('journal_voucher_details', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_voucher_id')->nullable()->after('acc_id');
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('receipt_voucher_id');
            
            $table->foreign('receipt_voucher_id')->references('id')->on('receipt_vouchers')->onDelete('set null');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_voucher_details', function (Blueprint $table) {
            $table->dropForeign(['receipt_voucher_id']);
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn(['receipt_voucher_id', 'sales_order_id']);
        });
    }
};
