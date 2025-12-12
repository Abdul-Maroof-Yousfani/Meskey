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
        Schema::table('delivery_challans', function (Blueprint $table) {
          

            DB::statement("ALTER TABLE `delivery_challans` 
            MODIFY `location_id` BIGINT UNSIGNED NULL,
            MODIFY `arrival_id` BIGINT UNSIGNED NULL,
            MODIFY `subarrival_id` BIGINT UNSIGNED NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_challan', function (Blueprint $table) {
            //
        });
    }
};
