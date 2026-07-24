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
        Schema::create('milling_rate_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('milling_rate_id');
            $table->unsignedBigInteger('variable_id');
            $table->decimal('value', 10, 2);
            $table->timestamps();

            $table->foreign('milling_rate_id')->references('id')->on('milling_rates')->onDelete('cascade');
            $table->foreign('variable_id')->references('id')->on('variables')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milling_rate_variables');
    }
};
