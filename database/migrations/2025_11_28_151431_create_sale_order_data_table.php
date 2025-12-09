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
        Schema::create('sales_order_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->float("qty");
            $table->float("rate");
            $table->foreignId("brand_id")->constrained("brands")->cascadeOnDelete();
            $table->string("pack_size");
            $table->foreignId("bag_type")->constrained("bag_types")->cascadeOnDelete();
            $table->string("bag_size");
            $table->string("no_of_bags");
            $table->foreignId("sale_order_id")->constrained("sales_orders")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_order_data');
    }
};
