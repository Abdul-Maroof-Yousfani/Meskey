<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update export_orders table
        Schema::table('export_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('export_orders', 'export_soda_id')) {
                $table->unsignedBigInteger('export_soda_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('export_orders', 'quotation_id')) {
                $table->unsignedBigInteger('quotation_id')->nullable()->after('export_soda_id');
            }

            // Foreign Keys
            try {
                $table->foreign('export_soda_id')->references('id')->on('export_soda_fields')->nullOnDelete();
                $table->foreign('quotation_id')->references('id')->on('quotations')->nullOnDelete();
            } catch (\Exception $e) {
                // Already exists or table missing
            }

            if (!Schema::hasColumn('export_orders', 'visual_name')) {
                $table->string('visual_name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('export_orders', 'additional_info')) {
                $table->text('additional_info')->nullable();
            }
            if (!Schema::hasColumn('export_orders', 'commission_percentage')) {
                $table->decimal('commission_percentage', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('export_orders', 'commission_amount_per_ton')) {
                $table->decimal('commission_amount_per_ton', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('export_orders', 'commission')) {
                $table->decimal('commission', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('export_orders', 'company_location_ids')) {
                $table->json('company_location_ids')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('export_orders', 'arrival_location_ids')) {
                $table->json('arrival_location_ids')->nullable()->after('company_location_ids');
            }
            if (!Schema::hasColumn('export_orders', 'arrival_sub_location_ids')) {
                $table->json('arrival_sub_location_ids')->nullable()->after('arrival_location_ids');
            }
        });

        // 2. Update export_order_packing_items table
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_order_packing_items', 'maunds')) {
                $table->decimal('maunds', 15, 4)->default(0)->after('metric_tons');
            }
            if (!Schema::hasColumn('export_order_packing_items', 'stuffing_maunds')) {
                $table->decimal('stuffing_maunds', 15, 4)->default(0)->after('stuffing_in_container');
            }
            if (!Schema::hasColumn('export_order_packing_items', 'rate_per_maund')) {
                $table->decimal('rate_per_maund', 15, 4)->default(0)->after('rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            try {
                $table->dropForeign(['export_soda_id']);
                $table->dropForeign(['quotation_id']);
            } catch (\Exception $e) { }
            
            $cols = [
                'export_soda_id', 'quotation_id', 'visual_name', 'additional_info',
                'commission_percentage', 'commission_amount_per_ton', 'commission',
                'company_location_ids', 'arrival_location_ids', 'arrival_sub_location_ids',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('export_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $cols = ['maunds', 'stuffing_maunds', 'rate_per_maund'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('export_order_packing_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
