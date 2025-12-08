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
        Schema::create('delivery_challan_delivery_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId("delivery_challan_id")->constrained("delivery_challans")->cascadeOnDelete();
            $table->foreignId("delivery_order_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->float("qty")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_challan_delivery_order');
    }
};
