<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->text('additional_info')->after('application_law')->nullable();
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_order_packing_items', 'company_location_id')) {
                $table->foreignId('company_location_id')->nullable()->after('export_order_id')->constrained('company_locations', 'eo_pi_loc_foreign');
            }
            
            $table->integer('extra_bags')->after('no_of_bags')->default(0);
            $table->integer('empty_bags')->after('extra_bags')->default(0);
            $table->integer('total_bags')->after('empty_bags')->default(0);
            
            $table->foreignId('thread_color_id')->nullable()->after('bag_color_id')->constrained('colors');
            $table->foreignId('stitching_id')->nullable()->after('thread_color_id')->constrained('stitchings');
            
            $table->decimal('min_weight_empty_bags', 10, 2)->after('no_of_containers')->default(0);
            $table->json('fumigation_company_id')->after('min_weight_empty_bags')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn('additional_info');
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);
            $table->dropForeign(['thread_color_id']);
            $table->dropForeign(['stitching_id']);
            
            $table->dropColumn([
                'company_location_id',
                'extra_bags',
                'empty_bags',
                'total_bags',
                'thread_color_id',
                'stitching_id',
                'min_weight_empty_bags',
                'fumigation_company_id'
            ]);
        });
    }
};
