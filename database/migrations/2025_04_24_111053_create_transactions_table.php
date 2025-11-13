<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
            $table->unsignedBigInteger('counter_account_id')->nullable();
            $table->unsignedBigInteger('counter_account_ref')->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('is_opening_balance', ['yes', 'no'])->default('no');
            $table->string('grn_no')->nullable();
            $table->string('action')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('purpose', 255)->nullable();
            $table->integer('stock')->default(0)->nullable();
            $table->text('remarks')->nullable();
            $table->string('payment_against')->nullable()->comment('Type of payment against (e.g., invoice, bill, expense)');
            $table->string('against_reference_no')->nullable()->comment('Reference number of the document this payment is against');
            // Adding index for better query performance
            $table->index(['payment_against']);
            $table->index(['against_reference_no']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            //Froeght Reference
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
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
