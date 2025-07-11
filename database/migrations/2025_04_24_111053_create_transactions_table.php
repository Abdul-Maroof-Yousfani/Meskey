<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('voucher_no');
            $table->date('voucher_date');
            $table->foreignId('transaction_voucher_type_id')->constrained('transaction_voucher_types')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->nullable();
            $table->string('account_unique_no')->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('is_opening_balance', ['yes', 'no'])->default('no');
            $table->string('action')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('remarks')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->nullable()->nullOnDelete();
            $table->foreignId('updated_by')->constrained('users')->nullable()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['account_id', 'type']);
            $table->index(['voucher_no']);
            $table->index(['voucher_date']);
            $table->index(['transaction_voucher_type_id']);
            $table->index(['status']);
            $table->index(['account_unique_no']);
            $table->index(['created_by']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
