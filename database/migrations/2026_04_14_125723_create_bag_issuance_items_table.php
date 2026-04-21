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
        Schema::create('bag_issuance_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bag_issuance_id');
            $table->unsignedBigInteger('job_order_id')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('bag_issuance_id')->references('id')->on('bag_issuances')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('products');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('unit_id')->references('id')->on('unit_of_measures');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_issuance_items');
    }
};
