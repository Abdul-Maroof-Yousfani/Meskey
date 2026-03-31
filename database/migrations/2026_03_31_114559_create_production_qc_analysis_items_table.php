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
        Schema::create('production_qc_analysis_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_analysis_id');
            $table->time('analysis_time');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();

            $table->foreign('production_analysis_id', 'pa_items_parent_fk_pivot')
                  ->references('id')->on('production_analysis')
                  ->onDelete('cascade');
            $table->foreign('unit_id', 'pa_items_unit_fk_pivot')
                  ->references('id')->on('unit_of_measures')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_analysis_items');
    }
};
