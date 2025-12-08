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
        Schema::create('sales_inquiry_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("inquiry_id")->constrained("sales_inquiries")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->float("bag_size");
            $table->float("no_of_bags");
            $table->foreignId("bag_type")->constrained("bag_types")->cascadeOnDelete();
            $table->foreignId("brand_id")->constrained("brands")->cascadeOnDelete();
            $table->float("pack_size");
            $table->float("qty");
            $table->float("rate");
            $table->string("description");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_data');
    }
};
