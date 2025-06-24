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
        Schema::create('payment_voucher_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_voucher_id');
            $table->unsignedBigInteger('payment_request_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_voucher_id')->references('id')->on('payment_vouchers');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_data');
    }
};
