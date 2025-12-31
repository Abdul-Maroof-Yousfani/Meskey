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
        Schema::table('sales_first_weighbridges', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['delivery_order_id']);

            // Rename the column
            $table->renameColumn('delivery_order_id', 'loading_program_item_id');

            // Add the new foreign key constraint
            $table->foreign('loading_program_item_id')->references('id')->on('loading_program_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_first_weighbridges', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['loading_program_item_id']);

            // Rename the column back
            $table->renameColumn('loading_program_item_id', 'delivery_order_id');

            // Add back the old foreign key constraint
            $table->foreign('delivery_order_id')->references('id')->on('delivery_order')->onDelete('cascade');
        });
    }
};
