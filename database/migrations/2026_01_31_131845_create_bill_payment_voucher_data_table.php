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
        Schema::create('bill_payment_voucher_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("payment_voucher_id")->constrained("payment_vouchers")->cascadeOnDelete();
            $table->foreignId("purchase_bill_id")->constrained("purchase_bills")->cascadeOnDelete();
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payment_voucher_data');
    }
};
