<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loading_program_items', function (Blueprint $table) {
            // Adding first_weighbridge_id after sub_arrival_location_id
            $table->foreignId('first_weighbridge_location_id')
                ->nullable()
                ->after('sub_arrival_location_id')
                ->constrained('arrival_sub_locations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_program_items', function (Blueprint $table) {
            $table->dropForeign(['first_weighbridge_location_id']);
            $table->dropColumn('first_weighbridge_location_id');
        });
    }
};