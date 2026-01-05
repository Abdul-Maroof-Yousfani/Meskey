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
        Schema::create('plant_breakdown_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('plant_breakdown_id');
            $table->unsignedBigInteger('breakdown_type_id');
            $table->time('from');
            $table->time('to');
            $table->decimal('hours', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('plant_breakdown_id')->references('id')->on('plant_breakdowns')->onDelete('cascade');
            $table->foreign('breakdown_type_id')->references('id')->on('plant_breakdown_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_breakdown_items');
    }
};
