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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['arrival_location_id']);
            $table->dropForeign(['arrival_sub_location_id']);

            $table->foreignId('arrival_location_id')->nullable()->change();
            $table->foreignId('arrival_sub_location_id')->nullable()->change();
            
            $table->foreign('arrival_location_id')->references('id')->on('arrival_locations');
            $table->foreign('arrival_sub_location_id')->references('id')->on('arrival_sub_locations');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            //
        });
    }
};
