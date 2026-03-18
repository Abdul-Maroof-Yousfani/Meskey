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
        Schema::dropIfExists('purchase_bill_purchase_return');

        Schema::create('purchase_bill_purchase_return', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_bill_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->timestamps();

            $table->foreign('purchase_bill_id')->references('id')->on('purchase_bills')->onDelete('cascade');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');

            $table->unique(['purchase_bill_id', 'purchase_return_id'], 'pb_pr_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_purchase_return');
    }
};
