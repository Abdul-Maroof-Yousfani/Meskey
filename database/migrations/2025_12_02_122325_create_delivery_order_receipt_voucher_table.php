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
        Schema::create('delivery_order_receipt_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId("delivery_order_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->foreignId("receipt_voucher_id")->constrained("receipt_vouchers")->cascadeOnDelete();
            $table->float("amount")->nullable();
            $table->float("withhold_amount")->nullable();
            $table->float("last_withhold_amount")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_advance_amounts');
    }
};
