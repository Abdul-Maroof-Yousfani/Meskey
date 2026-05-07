<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_order_packing_items', 'extra_bags_percentage')) {
                $table->decimal('extra_bags_percentage', 8, 2)->nullable()->default(0)->after('extra_bags');
            }
            if (!Schema::hasColumn('export_order_packing_items', 'empty_bags_percentage')) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            }
            if (!Schema::hasColumn('export_order_packing_items', 'inspection_by')) {
                $table->json('inspection_by')->nullable()->after('fumigation_company_id');
            }
        });

        Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_order_packing_sub_items', 'empty_bags_percentage')) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            }
            if (!Schema::hasColumn('export_order_packing_sub_items', 'extra_bags_percentage')) {
                $table->decimal('extra_bags_percentage', 8, 2)->nullable()->default(0)->after('extra_bags');
            }
        });

        Schema::table('export_orders', function (Blueprint $table) {
            if (Schema::hasColumn('export_orders', 'vessel_name')) {
                $table->dropColumn('vessel_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('export_orders', 'vessel_name')) {
                $table->string('vessel_name')->nullable()->after('shipment_delivery_date_to');
            }
        });

        Schema::table('export_order_packing_sub_items', function (Blueprint $table) {
            foreach (['empty_bags_percentage', 'extra_bags_percentage'] as $column) {
                if (Schema::hasColumn('export_order_packing_sub_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            foreach (['extra_bags_percentage', 'empty_bags_percentage', 'inspection_by'] as $column) {
                if (Schema::hasColumn('export_order_packing_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
