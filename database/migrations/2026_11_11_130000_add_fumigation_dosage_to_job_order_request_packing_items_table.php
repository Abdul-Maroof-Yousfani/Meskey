<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fumigation Dosage per job order request packing row (filled from export_orders.fumigation_dosage)
    public function up(): void
    {
        if (!Schema::hasColumn('job_order_request_packing_items', 'fumigation_dosage')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->string('fumigation_dosage', 100)->nullable()->after('fumigation_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_order_request_packing_items', 'fumigation_dosage')) {
            Schema::table('job_order_request_packing_items', function (Blueprint $table) {
                $table->dropColumn('fumigation_dosage');
            });
        }
    }
};
