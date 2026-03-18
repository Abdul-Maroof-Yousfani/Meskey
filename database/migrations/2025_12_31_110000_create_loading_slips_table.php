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
        Schema::create('loading_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_program_item_id')->constrained('loading_program_items')->onDelete('cascade');
            $table->string('customer');
            $table->string('commodity');
            $table->decimal('so_qty', 15, 2);
            $table->decimal('do_qty', 15, 2);
            $table->string('factory');
            $table->string('gala')->nullable();
            $table->integer('no_of_bags');
            $table->decimal('bag_size', 10, 2);
            $table->decimal('kilogram', 15, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique('loading_program_item_id', 'unique_loading_program_item_per_slip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_slips');
    }
};
