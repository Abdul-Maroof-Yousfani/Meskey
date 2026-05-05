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
        Schema::create('export_outer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loading_program_item_id');
            $table->string('item_name');
            $table->decimal('weight', 10, 3)->default(0)->comment('Per item weight');
            $table->decimal('qty', 10, 3)->default(0);
            $table->decimal('total_weight', 10, 3)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('loading_program_item_id')->references('id')->on('loading_program_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_outer_items');
    }
};
