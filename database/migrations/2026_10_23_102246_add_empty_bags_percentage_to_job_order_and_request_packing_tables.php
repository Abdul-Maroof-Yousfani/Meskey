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
        if (Schema::hasTable('job_order_packing_items') && !Schema::hasColumn('job_order_packing_items', 'empty_bags_percentage')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            });
        }

        if (Schema::hasTable('job_order_packing_sub_items') && !Schema::hasColumn('job_order_packing_sub_items', 'empty_bags_percentage')) {
            Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            });
        }

        if (Schema::hasTable('job_order_request_packing_items') && !Schema::hasColumn('job_order_request_packing_items', 'empty_bags_percentage')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            });
        }

        if (Schema::hasTable('job_order_request_packing_sub_items') && !Schema::hasColumn('job_order_request_packing_sub_items', 'empty_bags_percentage')) {
            Schema::table('job_order_request_packing_sub_items', function (Blueprint $table) {
                $table->decimal('empty_bags_percentage', 8, 2)->nullable()->default(0)->after('empty_bags');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_order_packing_items') && Schema::hasColumn('job_order_packing_items', 'empty_bags_percentage')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                $table->dropColumn('empty_bags_percentage');
            });
        }

        if (Schema::hasTable('job_order_packing_sub_items') && Schema::hasColumn('job_order_packing_sub_items', 'empty_bags_percentage')) {
            Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
                $table->dropColumn('empty_bags_percentage');
            });
        }

        if (Schema::hasTable('job_order_request_packing_items') && Schema::hasColumn('job_order_request_packing_items', 'empty_bags_percentage')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->dropColumn('empty_bags_percentage');
            });
        }

        if (Schema::hasTable('job_order_request_packing_sub_items') && Schema::hasColumn('job_order_request_packing_sub_items', 'empty_bags_percentage')) {
            Schema::table('job_order_request_packing_sub_items', function (Blueprint $table) {
                $table->dropColumn('empty_bags_percentage');
            });
        }
    }
};
