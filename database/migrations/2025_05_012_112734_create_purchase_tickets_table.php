<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('qc_product')->nullable();
            $table->enum('is_custom_qc', ['yes', 'no'])->default('no');
            $table->decimal('bag_weight', 10, 2)->nullable();

            $table->unsignedBigInteger('purchase_order_id')->nullable();

            $table->enum('qc_status', ['resampling', 'pending', 'rejected', 'approved'])->nullable();
            $table->enum('freight_status', ['completed', 'pending'])->default('pending')->nullable();
            $table->string('payment_request_status',)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('qc_product')->references('id')->on('products')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('arrival_purchase_orders');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_tickets');
    }
};
