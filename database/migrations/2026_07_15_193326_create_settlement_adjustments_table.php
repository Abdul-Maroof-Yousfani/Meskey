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
        Schema::create('settlement_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type')->nullable(); // e.g. journal_voucher, receipt_voucher_advance
            $table->unsignedBigInteger('reference_id')->nullable(); // e.g. journal_voucher_id
            $table->string('voucher_no')->nullable(); // e.g. Delivery Order's reference_no
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_adjustments');
    }
};
