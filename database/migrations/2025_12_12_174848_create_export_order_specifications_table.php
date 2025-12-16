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
        Schema::create('export_order_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_order_id');
            $table->foreignId('product_slab_type_id')->constrained()->onDelete('cascade');
            $table->string('spec_name');
            $table->string('spec_value')->nullable();
            $table->string('uom')->nullable();
            $table->enum('value_type', ['min', 'max'])->default('min');
            $table->timestamps();

            $table->foreign('export_order_id')
                ->references('id')->on('export_orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_order_specifications');
    }
};
