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
            if (!Schema::hasColumn('journal_voucher_details', 'delivery_order_id')) {
                $table->unsignedBigInteger('delivery_order_id')->nullable()->after('sales_order_id');
                $table->foreign('delivery_order_id')->references('id')->on('delivery_order')->onDelete('set null');
            }
            if (!Schema::hasColumn('journal_voucher_details', 'reference_jv_id')) {
                $table->unsignedBigInteger('reference_jv_id')->nullable()->after('delivery_order_id');
                $table->foreign('reference_jv_id')->references('id')->on('journal_vouchers')->onDelete('set null');
            }
            if (!Schema::hasColumn('journal_voucher_details', 'reference_jv_detail_id')) {
                $table->unsignedBigInteger('reference_jv_detail_id')->nullable()->after('reference_jv_id');
                $table->foreign('reference_jv_detail_id')->references('id')->on('journal_voucher_details')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_voucher_details', function (Blueprint $table) {
            if (Schema::hasColumn('journal_voucher_details', 'reference_jv_detail_id')) {
                $table->dropForeign(['reference_jv_detail_id']);
                $table->dropColumn('reference_jv_detail_id');
            }
            if (Schema::hasColumn('journal_voucher_details', 'reference_jv_id')) {
                $table->dropForeign(['reference_jv_id']);
                $table->dropColumn('reference_jv_id');
            }
            if (Schema::hasColumn('journal_voucher_details', 'delivery_order_id')) {
                $table->dropForeign(['delivery_order_id']);
                $table->dropColumn('delivery_order_id');
            }
        });
    }
};
