<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_data_id')->constrained()->onDelete('cascade');
            $table->enum('module_type', ['ticket', 'purchase_order']);
            $table->enum('request_type', ['payment', 'freight_payment']);
            $table->decimal('other_deduction_kg', 10, 4)->nullable();
            $table->decimal('other_deduction_value', 10, 4)->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_requests');
    }
};
