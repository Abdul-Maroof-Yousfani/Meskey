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
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['bag_type_id']);
            
            // Change bag_type_id to JSON
            $table->json('bag_type_id')->nullable()->change();
            
            // Make bag_size and no_of_bags nullable since they're now in sub-items
            $table->decimal('bag_size', 8, 2)->nullable()->change();
            $table->integer('no_of_bags')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            // Revert bag_type_id to foreignId
            $table->foreignId('bag_type_id')->nullable()->change();
            $table->foreign('bag_type_id')->references('id')->on('bag_types');
            
            // Revert bag_size and no_of_bags to not nullable
            $table->decimal('bag_size', 8, 2)->nullable(false)->change();
            $table->integer('no_of_bags')->nullable(false)->change();
        });
    }
};
