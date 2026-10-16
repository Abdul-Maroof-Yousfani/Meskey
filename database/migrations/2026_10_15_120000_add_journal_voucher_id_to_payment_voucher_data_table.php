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
            if (!Schema::hasColumn('payment_voucher_data', 'journal_voucher_id')) {
                $table->unsignedBigInteger('journal_voucher_id')->nullable()->after('delivery_order_id');
                $table->foreign('journal_voucher_id')->references('id')->on('journal_vouchers')->onDelete('set null');
            }
            if (!Schema::hasColumn('payment_voucher_data', 'journal_voucher_detail_id')) {
                $table->unsignedBigInteger('journal_voucher_detail_id')->nullable()->after('journal_voucher_id');
                $table->foreign('journal_voucher_detail_id')->references('id')->on('journal_voucher_details')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_voucher_data', function (Blueprint $table) {
            if (Schema::hasColumn('payment_voucher_data', 'journal_voucher_detail_id')) {
                $table->dropForeign(['journal_voucher_detail_id']);
                $table->dropColumn('journal_voucher_detail_id');
            }
            if (Schema::hasColumn('payment_voucher_data', 'journal_voucher_id')) {
                $table->dropForeign(['journal_voucher_id']);
                $table->dropColumn('journal_voucher_id');
            }
        });
    }
};
