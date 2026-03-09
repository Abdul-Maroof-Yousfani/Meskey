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
        Schema::table('delivery_order_receipt_voucher', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_voucher_advance_id')->nullable()->after('receipt_voucher_id');
            // $table->foreign('receipt_voucher_advance_id')->references('id')->on('receipt_voucher_advances')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_receipt_voucher', function (Blueprint $table) {
            $table->dropForeign(['receipt_voucher_advance_id']);
            $table->dropColumn('receipt_voucher_advance_id');
        });
    }
};
