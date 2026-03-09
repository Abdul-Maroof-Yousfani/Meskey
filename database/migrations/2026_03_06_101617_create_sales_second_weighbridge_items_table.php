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
        Schema::create('sales_second_weighbridge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('second_weighbridge_id')->constrained('sales_second_weighbridges')->onDelete('cascade');
            $table->foreignId('delivery_order_id')->constrained('delivery_order')->onDelete('cascade');
            $table->decimal('net_weight', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_second_weighbridge_items');
    }
};
