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
        Schema::create('purchase_return_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('purchase_bill_data_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('rate', 15, 2);
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('packing')->nullable();
            $table->integer('no_of_bags')->nullable();
            $table->enum('am_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('am_change_mode')->default(false);
            $table->timestamps();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns');
            $table->foreign('purchase_bill_data_id')->references('id')->on('purchase_bills_data');
            $table->foreign('item_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_data');
    }
};
