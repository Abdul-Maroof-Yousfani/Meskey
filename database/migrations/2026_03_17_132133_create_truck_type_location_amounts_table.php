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
        Schema::create('truck_type_location_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('truck_type_id'); // refers to arrival_truck_types.id
            $table->unsignedBigInteger('company_location_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            // Foreign keys (optional but recommended for pivot tables)
            $table->foreign('truck_type_id')->references('id')->on('arrival_truck_types')->onDelete('cascade');
            $table->foreign('company_location_id')->references('id')->on('company_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_type_location_amounts');
    }
};
