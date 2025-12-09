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
        Schema::create('delivery_order_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->float("qty");
            $table->float("rate");
            $table->foreignId("brand_id")->constrained("brands")->cascadeOnDelete();
            $table->string("pack_size")->nullable();
            $table->foreignId("delivery_order_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->string("no_of_bags");
            $table->string("bag_size");
            $table->foreignId("bag_type")->constrained("bag_types")->cascadeOnDelete();
            $table->foreignId("so_data_id")->constrained("sales_order_data")->cascadeOnDelete();
            $table->string("description");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_data');
    }
};
