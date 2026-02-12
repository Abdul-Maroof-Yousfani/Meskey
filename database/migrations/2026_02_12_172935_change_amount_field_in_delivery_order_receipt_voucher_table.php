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
            $table->decimal("amount", 15, 2)->change();
            $table->decimal("withhold_amount", 15, 2)->change();
            $table->decimal("last_withhold_amount", 15, 2)->change();
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_receipt_voucher', function (Blueprint $table) {
            //
        });
    }
};
