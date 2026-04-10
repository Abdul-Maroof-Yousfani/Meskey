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
            // Change columns from bigint to string to support comma-separated values
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
            // Revert back to bigint unsigned
            $table->unsignedBigInteger('arrival_location_id')->nullable()->change();
            $table->unsignedBigInteger('sub_arrival_location_id')->nullable()->change();
        });
    }
};
