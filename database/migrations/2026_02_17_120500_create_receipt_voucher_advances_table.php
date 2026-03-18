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
        Schema::create('receipt_voucher_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_voucher_id')->constrained('receipt_vouchers')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('adv_no'); // e.g., ADV-001
            $table->decimal('amount', 20, 2);
            $table->foreignId('tax_id')->nullable()->constrained('taxes');
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2);
            $table->text('line_desc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_voucher_advances');
    }
};
