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
        Schema::create('delivery_order_transactions', function (Blueprint $table) {
            $table->id();
            $table->float("so_amount");
            $table->float("percentage")->nullable();
            $table->float("advance_amount");
            $table->foreignId("sale_order_id")->constrained("sales_orders")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_transactions');
    }
};
