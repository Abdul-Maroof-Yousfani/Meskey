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
        if (Schema::hasTable('job_order_packing_items') && !Schema::hasColumn('job_order_packing_items', 'fumigation_type')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                $table->string('fumigation_type', 100)->nullable()->after('fumigation_company_id');
            });
        }

        if (Schema::hasTable('job_orders') && !Schema::hasColumn('job_orders', 'fumigation_type')) {
            Schema::table('job_orders', function (Blueprint $table) {
                $table->string('fumigation_type', 100)->nullable()->after('arrival_locations');
            });
        }

        if (Schema::hasTable('job_order_request_packing_items') && !Schema::hasColumn('job_order_request_packing_items', 'fumigation_type')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->string('fumigation_type', 100)->nullable()->after('fumigation_company_id');
            });
        }

        if (Schema::hasTable('job_order_requests') && !Schema::hasColumn('job_order_requests', 'fumigation_type')) {
            Schema::table('job_order_requests', function (Blueprint $table) {
                $table->string('fumigation_type', 100)->nullable()->after('arrival_locations');
            });
        }

        if (Schema::hasTable('settings')) {
            $existing = DB::table('settings')
                ->where('key', 'fumigation_types')
                ->where('group', 'production')
                ->first();

            if (!$existing) {
                DB::table('settings')->insert([
                    'settable_type' => null,
                    'settable_id'   => null,
                    'group'         => 'production',
                    'key'           => 'fumigation_types',
                    'value'         => json_encode(['phosphorate', 'methale']),
                    'type'          => 'json',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_order_packing_items') && Schema::hasColumn('job_order_packing_items', 'fumigation_type')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                $table->dropColumn('fumigation_type');
            });
        }

        if (Schema::hasTable('job_orders') && Schema::hasColumn('job_orders', 'fumigation_type')) {
            Schema::table('job_orders', function (Blueprint $table) {
                $table->dropColumn('fumigation_type');
            });
        }

        if (Schema::hasTable('job_order_request_packing_items') && Schema::hasColumn('job_order_request_packing_items', 'fumigation_type')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->dropColumn('fumigation_type');
            });
        }
        
        if (Schema::hasTable('job_order_requests') && Schema::hasColumn('job_order_requests', 'fumigation_type')) {
            Schema::table('job_order_requests', function (Blueprint $table) {
                $table->dropColumn('fumigation_type');
            });
        }
    }
};
