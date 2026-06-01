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
        Schema::create('c_freight_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('c_freight_id')->nullable();
            
            $table->string('third_party')->nullable();
            $table->string('shipping_line')->nullable();
            $table->string('container_size')->nullable();
            $table->string('port')->nullable();
            $table->string('price')->nullable();
            $table->boolean('is_approved')->default(false);
            
            $table->timestamps();
            
            $table->foreign('c_freight_id')->references('id')->on('c_freights')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_freight_rates');
    }
};
