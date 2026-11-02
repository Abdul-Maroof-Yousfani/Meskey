<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add fumigation_type to export_orders
        if (Schema::hasTable('export_orders') && !Schema::hasColumn('export_orders', 'fumigation_type')) {
            Schema::table('export_orders', function (Blueprint $table) {
                $table->string('fumigation_type', 100)->nullable()->after('fumigation_by');
            });
        }

        // 2. Update foreign key on export_order_packing_sub_items to reference sizes table
        if (Schema::hasTable('export_order_packing_sub_items')) {
            // Drop existing foreign key constraint if it exists
            Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
                try {
                    $table->dropForeign('export_order_packing_sub_items_bag_size_id_foreign');
                } catch (\Throwable $e) {
                    // Ignore if already dropped
                }
            });

            // Set any orphaned bag_size_id that doesn't exist in sizes table to NULL
            if (Schema::hasTable('sizes')) {
                DB::statement("UPDATE export_order_packing_sub_items SET bag_size_id = NULL WHERE bag_size_id IS NOT NULL AND bag_size_id NOT IN (SELECT id FROM sizes)");

                // Add foreign key referencing sizes(id)
                Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
                    $table->foreign('bag_size_id', 'export_order_packing_sub_items_bag_size_id_foreign')
                        ->references('id')
                        ->on('sizes')
                        ->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert foreign key on export_order_packing_sub_items to bag_packings
        if (Schema::hasTable('export_order_packing_sub_items')) {
            Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
                try {
                    $table->dropForeign('export_order_packing_sub_items_bag_size_id_foreign');
                } catch (\Throwable $e) {
                }
            });

            if (Schema::hasTable('bag_packings')) {
                DB::statement("UPDATE export_order_packing_sub_items SET bag_size_id = NULL WHERE bag_size_id IS NOT NULL AND bag_size_id NOT IN (SELECT id FROM bag_packings)");

                Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
                    $table->foreign('bag_size_id', 'export_order_packing_sub_items_bag_size_id_foreign')
                        ->references('id')
                        ->on('bag_packings')
                        ->nullOnDelete();
                });
            }
        }

        // 2. Drop fumigation_type from export_orders
        if (Schema::hasTable('export_orders') && Schema::hasColumn('export_orders', 'fumigation_type')) {
            Schema::table('export_orders', function (Blueprint $table) {
                $table->dropColumn('fumigation_type');
            });
        }
    }
};

