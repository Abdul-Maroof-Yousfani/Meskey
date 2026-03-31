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
        Schema::create('production_qc_analysis_item_slabs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_analysis_item_id');
            $table->unsignedBigInteger('slab_type_id');
            $table->string('production_analysis_value')->nullable();
            $table->timestamps();

            $table->foreign('production_analysis_item_id', 'pa_item_slabs_item_fk')
                  ->references('id')->on('production_qc_analysis_items')
                  ->onDelete('cascade');
            $table->foreign('slab_type_id', 'pa_item_slabs_type_fk')
                  ->references('id')->on('product_slab_types')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_qc_analysis_item_slabs');
    }
};
