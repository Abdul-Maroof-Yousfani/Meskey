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
        Schema::create('delivery_challan', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            $table->foreignId("delivery_order_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->string("reference_number");
            $table->date("dispatch_date");
            $table->string("dc_no");
            $table->enum("sauda_type", ["pohanch", "x-mill"]);
            $table->foreignId("location_id")->constrained("locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->constrained("arrival_locations")->cascadeOnDelete();
            $table->string("remarks");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_challan');
    }
};
