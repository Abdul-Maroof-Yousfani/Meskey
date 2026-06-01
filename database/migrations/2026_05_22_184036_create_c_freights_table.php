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
        Schema::create('c_freights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_order_id')->nullable();
            
            // Request Form Data
            $table->integer('requested_containers')->nullable();
            $table->string('free_days')->nullable();
            $table->string('etr')->nullable();
            
            // Final Booking Data (Image 3)
            $table->string('booking_no')->nullable();
            $table->string('bl_number')->nullable();
            $table->string('vessel_name')->nullable();
            $table->date('cutoff_si')->nullable();
            $table->date('cutoff_cy')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->string('through_logistic')->nullable();
            $table->string('return_port')->nullable();
            $table->string('status')->default('Pending Rates'); // Pending Rates, Booked
            
            $table->timestamps();
            
            $table->foreign('export_order_id')->references('id')->on('export_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_freights');
    }
};
