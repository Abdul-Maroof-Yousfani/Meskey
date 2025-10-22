<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_receiving_no')->unique();
            $table->date('order_receiving_date');
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('am_approval_status')->default('pending');
            $table->Integer('am_change_made')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receivings');
    }
};
