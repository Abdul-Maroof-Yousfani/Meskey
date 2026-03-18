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
        Schema::create('production_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_voucher_id');
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->unsignedBigInteger('product_id'); // commodity
            $table->unsignedBigInteger('location_id'); // arrival_sub_location_id
            $table->decimal('qty', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('production_slots')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('arrival_sub_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inputs');
    }
};
