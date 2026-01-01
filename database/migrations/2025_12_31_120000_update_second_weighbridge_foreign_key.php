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
        Schema::table('sales_second_weighbridges', function (Blueprint $table) {
            // Drop the existing foreign key constraint and column
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');

            // Add the new foreign key column
            $table->foreignId('loading_slip_id')->constrained('loading_slips')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_second_weighbridges', function (Blueprint $table) {
            // Drop the new foreign key constraint and column
            $table->dropForeign(['loading_slip_id']);
            $table->dropColumn('loading_slip_id');

            // Add back the old foreign key column
            $table->foreignId('delivery_order_id')->constrained('delivery_order')->onDelete('cascade');
        });
    }
};
