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
        Schema::create('customer_advance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_advance_id')->constrained('customer_advances')->onDelete('cascade');
            $table->string('voucher_no')->nullable(); // new receipt voucher where it's used
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_advance_adjustments');
    }
};
