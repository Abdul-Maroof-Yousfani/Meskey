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
        Schema::create('arrival_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('broker_name')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('bilty_no')->nullable();
            $table->date('loading_date')->nullable();
            $table->string('loading_weight')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('first_weight')->nullable();
            $table->string('second_weight')->nullable();
            $table->string('net_weight')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_ticket');
    }
};
