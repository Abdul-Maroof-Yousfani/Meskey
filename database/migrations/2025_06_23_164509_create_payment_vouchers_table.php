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
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no')->unique();
            $table->date('pv_date');
            $table->string('ref_bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->string('cheque_no')->nullable();
            $table->date('cheque_date')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts');

            $table->integer('supplier_id')->nullable();
            $table->integer('bank_account_id')->nullable();
            $table->enum('bank_account_type', ['company', 'owner'])->nullable();

            $table->unsignedBigInteger('module_id')->nullable();
            $table->enum('module_type', ['raw_material_purchase', 'store_purchase'])->nullable();
            $table->enum('voucher_type', ['bank_payment_voucher', 'cash_payment_voucher']);
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean("is_direct")->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();

            // $table->foreign('account_id')->references('account_id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
