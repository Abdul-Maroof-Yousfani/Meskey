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
        Schema::table('production_analysis', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_location_id')->nullable()->after('location_id');
            $table->unsignedBigInteger('plant_id')->nullable()->after('arrival_location_id');
            
            // Make existing fields nullable for input analysis
            $table->unsignedBigInteger('brand_id')->nullable()->change();
            $table->unsignedBigInteger('bag_packing_id')->nullable()->change();
            $table->string('variety')->nullable()->change();
            $table->unsignedBigInteger('crop_year_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_analysis', function (Blueprint $table) {
            $table->dropColumn(['arrival_location_id', 'plant_id']);
        });
    }
};
