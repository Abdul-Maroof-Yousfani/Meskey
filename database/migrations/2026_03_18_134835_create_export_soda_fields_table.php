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
        Schema::create('export_soda_fields', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('bag_packing_id')->nullable();
            $table->unsignedBigInteger('incoterm_id')->nullable();
            $table->decimal('price_per_kg', 15, 4)->nullable();
            $table->decimal('price_per_mound', 15, 4)->nullable();
            $table->decimal('price_per_100_kg', 15, 4)->nullable();
            $table->decimal('quantity_in_kg', 15, 4)->nullable();
            $table->decimal('quantity_in_ton', 15, 4)->nullable();
            $table->unsignedBigInteger('mode_of_term_id')->nullable();
            $table->date('shipment_period')->nullable();
            $table->decimal('commission', 15, 2)->nullable();
            $table->text('additional_info')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('buyer_id')->references('id')->on('users');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('bag_packing_id')->references('id')->on('bag_packings');
            $table->foreign('incoterm_id')->references('id')->on('inco_terms');
            $table->foreign('mode_of_term_id')->references('id')->on('mode_of_terms');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_soda_fields');
    }
};
