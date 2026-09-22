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
            $table->unsignedBigInteger('voucher_id')->nullable()->after('sales_order_id');
            $table->string('voucher_no')->nullable()->after('voucher_id');
            $table->string('voucher_type')->nullable()->after('voucher_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_voucher_details', function (Blueprint $table) {
            $table->dropColumn(['voucher_id', 'voucher_no', 'voucher_type']);
        });
    }
};
