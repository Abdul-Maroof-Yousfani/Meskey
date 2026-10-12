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
            if (!Schema::hasColumn('journal_voucher_details', 'customer_advance_id')) {
                $table->unsignedBigInteger('customer_advance_id')->nullable()->after('receipt_voucher_id');
                $table->foreign('customer_advance_id')->references('id')->on('customer_advances')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_voucher_details', function (Blueprint $table) {
            if (Schema::hasColumn('journal_voucher_details', 'customer_advance_id')) {
                $table->dropForeign(['customer_advance_id']);
                $table->dropColumn('customer_advance_id');
            }
        });
    }
};
