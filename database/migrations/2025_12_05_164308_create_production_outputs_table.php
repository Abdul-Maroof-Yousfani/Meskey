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
        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_voucher_id');
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->unsignedBigInteger('job_order_id')->nullable();
            $table->unsignedBigInteger('product_id'); // commodity
            $table->decimal('qty', 15, 2)->default(0);
            $table->integer('no_of_bags')->nullable();
            $table->string('bag_size')->nullable(); // 100g, 1kg, 5kg, 10kg, 15kg, 25kg, 50kg
            $table->decimal('avg_weight_per_bag', 10, 3)->nullable();
            $table->unsignedBigInteger('arrival_sub_location_id')->nullable(); // Changed from storage_location_id
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('production_slots')->onDelete('set null');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('arrival_sub_location_id')->references('id')->on('arrival_sub_locations')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
    }
};
