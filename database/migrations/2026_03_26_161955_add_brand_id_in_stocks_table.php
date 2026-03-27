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
            // $table->foreignId("company_location_id")->nullable()->constrained("company_locations");
            // $table->foreignId("arrival_id")->nulllable()->constrained("arrival_locations");
            // $table->foreignId("subarrival_id")->nullable()->constrained("sub_arrival_locations");
            $table->foreignId("brand_id")->nullable()->constrained("brands");
            $table->integer("bag_packing")->nullable();
            $table->foreignId("parentable_type")->nullable();
            $table->foreignId("parentable_id")->nullable();

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
