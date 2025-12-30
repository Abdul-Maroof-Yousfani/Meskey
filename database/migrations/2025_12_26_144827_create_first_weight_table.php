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
        Schema::create('first_weighbridge', function (Blueprint $table) {
            $table->id();
            $table->foreignId("do_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            $table->foreignId("commodity_id")->constrained("products")->cascadeOnDelete();
            $table->float("so_qty");
            $table->float("do_qty");
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->constrained("arrival_locations")->cascadeOnDelete();
            $table->foreignId("sub_arrival_id")->constrained("arrival_sub_locations")->cascadeOnDelete();
            

            $table->float("first_weight");
            $table->foreignId("truck_type")->constrained("arrival_truck_types")->cascadeOnDelete();
            $table->float("rate");
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('first_weight');
    }
};
