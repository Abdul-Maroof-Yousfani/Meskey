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
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId("company_location_id")->nullable()->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->nullable()->constrained("arrival_locations")->cascadeOnDelete();
            $table->foreignId("subarrival_id")->nullable()->constrained("arrival_sub_locations")->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            //
        });
    }
};
