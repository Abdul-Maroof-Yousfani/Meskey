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
        Schema::create('receipt_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_voucher_id')->constrained('receipt_vouchers')->cascadeOnDelete();
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_type'); // sale_order or sales_invoice
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->text('line_desc')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'receipt_voucher_items_reference_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_voucher_items');
    }
};


