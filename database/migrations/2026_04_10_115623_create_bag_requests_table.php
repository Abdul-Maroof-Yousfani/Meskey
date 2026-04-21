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
        Schema::create('bag_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->date('request_date');
            $table->unsignedBigInteger('arrival_location_id')->nullable();
            $table->unsignedBigInteger('gala_id')->nullable();
            $table->json('job_order_ids')->nullable();
            $table->text('remarks')->nullable();
            
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('company_location_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            
            $table->foreign('arrival_location_id')->references('id')->on('arrival_locations');
            $table->foreign('gala_id')->references('id')->on('arrival_sub_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_requests');
    }
};
