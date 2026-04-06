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
        Schema::create('production_analysis', function (Blueprint $table) {
            $table->id();
            $table->date("analysis_date");
            $table->foreignId("brand_id")->constrained("brands")->cascadeOnDelete();
            $table->foreignId("bag_packing_id")->constrained("bag_packings")->cascadeOnDelete();
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->string("variety");
            $table->foreignId("crop_year_id")->constrained("crop_years")->cascadeOnDelete();
            $table->string("milling_degree")->nullable();
            $table->string("inner_stitching")->nullable();
            $table->string("outer_stitching")->nullable();
            $table->string("remarks")->nullable();
            $table->enum("production_analysis_type", ["output", "input"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_analysis');
    }
};
