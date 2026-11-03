<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('machine_plan_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_location_id')->nullable()->after('company_id');
            $table->foreign('company_location_id')->references('id')->on('company_locations')->onDelete('set null');
        });

        // Each machine row in a plan is created against a job order
        Schema::table('production_voucher_machine_times', function (Blueprint $table) {
            $table->json('job_order_ids')->nullable()->after('production_machine_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_voucher_machine_times', function (Blueprint $table) {
            $table->dropColumn('job_order_ids');
        });

        Schema::table('machine_plan_settings', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);
            $table->dropColumn('company_location_id');
        });
    }
};
