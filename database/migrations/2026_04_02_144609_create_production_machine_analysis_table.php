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
        Schema::create('production_machine_analysis', function (Blueprint $table) {
            $table->id();
            $table->date('analysis_date');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('arrival_location_id');
            $table->unsignedBigInteger('plant_id');
            $table->unsignedBigInteger('production_machine_id');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_machine_analysis');
    }
};
