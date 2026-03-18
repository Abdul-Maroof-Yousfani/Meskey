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
        Schema::create('loading_program_items', function (Blueprint $table) {
            $table->id();
            $table->string("transaction_number")->unique();
            $table->foreignId('loading_program_id')->constrained('loading_programs')->onDelete('cascade');
            $table->string('truck_number');
            $table->string('container_number')->nullable();
            $table->string('packing')->nullable(); // bag size from delivery order
            $table->foreignId('brand_id')->nullable()->constrained('brands'); // from delivery order
            $table->foreignId('arrival_location_id')->constrained('arrival_locations');
            $table->foreignId('sub_arrival_location_id')->constrained('arrival_sub_locations');
            $table->string('driver_name')->nullable();
            $table->string('contact_details')->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_program_items');
    }
};
