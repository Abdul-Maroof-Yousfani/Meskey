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
        Schema::create('production_machines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('arrival_location_id');
            $table->unsignedBigInteger('plant_id');
            $table->string('name'); 
            $table->string('description')->nullable(); 
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes(); 
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('company_location_id')->references('id')->on('company_locations')->onDelete('cascade');
            $table->foreign('arrival_location_id')->references('id')->on('arrival_locations')->onDelete('cascade');
            $table->foreign('plant_id')->references('id')->on('plants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_machines');
    }
};
