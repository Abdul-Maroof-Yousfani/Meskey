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
        if (Schema::hasTable('job_order_request_packing_items')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                if (!Schema::hasColumn('job_order_request_packing_items', 'attachment')) {
                    $table->string('attachment')->nullable();
                }
            });
        }

        if (Schema::hasTable('job_order_packing_items')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                if (!Schema::hasColumn('job_order_packing_items', 'attachment')) {
                    $table->string('attachment')->nullable();
                }
            });
        }

        if (Schema::hasTable('export_order_packing_items')) {
            Schema::table('export_order_packing_items', function (Blueprint $table) {
                if (!Schema::hasColumn('export_order_packing_items', 'attachment')) {
                    $table->string('attachment')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_order_request_packing_items')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                if (Schema::hasColumn('job_order_request_packing_items', 'attachment')) {
                    $table->dropColumn('attachment');
                }
            });
        }

        if (Schema::hasTable('job_order_packing_items')) {
            Schema::table('job_order_packing_items', function (Blueprint $table) {
                if (Schema::hasColumn('job_order_packing_items', 'attachment')) {
                    $table->dropColumn('attachment');
                }
            });
        }

        if (Schema::hasTable('export_order_packing_items')) {
            Schema::table('export_order_packing_items', function (Blueprint $table) {
                if (Schema::hasColumn('export_order_packing_items', 'attachment')) {
                    $table->dropColumn('attachment');
                }
            });
        }
    }
};
