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
        Schema::create('production_machine_analysis_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_analysis_id');
            $table->time('analysis_time');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();

            $table->foreign('machine_analysis_id', 'fk_machine_analysis_item_parent')
                  ->references('id')->on('production_machine_analysis')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_machine_analysis_items');
    }
};
