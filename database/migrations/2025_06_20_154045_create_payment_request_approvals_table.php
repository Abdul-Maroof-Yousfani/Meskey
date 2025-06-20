<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests');
            $table->foreignId('payment_request_data_id')->constrained('payment_request_datas');
            $table->foreignId('purchase_order_id')->constrained('arrival_purchase_orders');
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('request_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_request_approvals');
    }
};
