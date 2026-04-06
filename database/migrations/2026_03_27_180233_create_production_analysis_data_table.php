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
        Schema::create('production_analysis_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("production_analysis_id")->constrained("production_analysis")->cascadeOnDelete();
            $table->time("analysis_time");
            $table->foreignId("slab_type_id")->constrained("product_slab_types")->cascadeOnDelete();
            $table->string("production_analysis_value");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_analysis_data');
    }
};
