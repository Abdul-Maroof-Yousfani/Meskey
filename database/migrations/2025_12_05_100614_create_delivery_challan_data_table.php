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
        Schema::create('delivery_challan_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("delivery_challan_id")->constrained("delivery_challans")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->float("qty");
            $table->float("rate");
            $table->foreignId("brand_id")->constrained("brands")->cascadeOnDelete();
            $table->float("no_of_bags");;
            $table->float("bag_size");
            $table->foreignId("do_data_id")->constrained("delivery_order_data")->cascadeOnDelete();
            $table->foreignId("bag_type")->constrained("bag_types")->cascadeOnDelete();
            $table->string("description");
            $table->string("truck_no");
            $table->string("bilty_no");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_challan_data');
    }
};
