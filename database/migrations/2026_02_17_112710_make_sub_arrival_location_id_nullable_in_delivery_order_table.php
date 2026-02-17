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
        Schema::table('delivery_order', function (Blueprint $table) {
            // Making arrival_location_id and sub_arrival_location_id nullable
            // Also ensuring they are strings to support the comma-separated format used in the controller
            $table->string('arrival_location_id')->nullable()->change();
            $table->string('sub_arrival_location_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->string('arrival_location_id')->nullable(false)->change();
            $table->string('sub_arrival_location_id')->nullable(false)->change();
        });
    }
};
