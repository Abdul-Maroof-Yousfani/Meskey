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
            // Drop constraints first


            // Drop foreign key constraints first
            DB::statement('ALTER TABLE sales_orders DROP FOREIGN KEY sales_orders_arrival_location_id_foreign');
            DB::statement('ALTER TABLE sales_orders DROP FOREIGN KEY sales_orders_arrival_sub_location_id_foreign');

            // Make columns nullable
            DB::statement('ALTER TABLE sales_orders MODIFY arrival_location_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE sales_orders MODIFY arrival_sub_location_id BIGINT UNSIGNED NULL');

            // Re-add foreign key constraints
            DB::statement('ALTER TABLE sales_orders ADD CONSTRAINT sales_orders_arrival_location_id_foreign FOREIGN KEY (arrival_location_id) REFERENCES arrival_locations(id)');
            DB::statement('ALTER TABLE sales_orders ADD CONSTRAINT sales_orders_arrival_sub_location_id_foreign FOREIGN KEY (arrival_sub_location_id) REFERENCES arrival_sub_locations(id)');
    
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
