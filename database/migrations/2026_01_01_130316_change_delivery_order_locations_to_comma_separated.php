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
            // Drop foreign key constraints first
            $table->dropForeign(['arrival_location_id']);
            $table->dropForeign(['sub_arrival_location_id']);

            // Change column types to string (this will handle the foreign key -> string conversion)
            $table->string('arrival_location_id')->nullable()->change();
            $table->string('subarrival_id')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            // Rename columns back
            $table->renameColumn('arrival_location_id', 'arrival_id');
            $table->renameColumn('sub_arrival_location_id', 'subarrival_id');

            // Change back to foreign keys (but first need to ensure data integrity)
            // Note: This assumes the data can be converted back to integers
            $table->foreignId('arrival_id')->nullable()->change();
            $table->foreignId('subarrival_id')->nullable()->change();

            // Add back foreign key constraints
            $table->foreign('arrival_id')->references('id')->on('arrival_locations')->onDelete('cascade');
            $table->foreign('subarrival_id')->references('id')->on('arrival_sub_locations')->onDelete('cascade');
        });
    }
};
