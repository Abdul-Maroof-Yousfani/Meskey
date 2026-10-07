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
        // 1. Add decision_making_time to arrival_sampling_requests
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_sampling_requests', 'decision_making_time')) {
                $table->dateTime('decision_making_time')->nullable()->after('decision_making');
            }
        });

        // Copy updated_at into decision_making_time for requests where a decision was made
        DB::statement("UPDATE arrival_sampling_requests SET decision_making_time = updated_at WHERE approved_status IN ('approved', 'rejected', 'resampling') AND updated_at IS NOT NULL");

        // 2. Add verify_at and contract_link_time to arrival_tickets
        Schema::table('arrival_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_tickets', 'verify_at')) {
                $table->dateTime('verify_at')->nullable()->after('ticket_verified_by');
            }
            if (!Schema::hasColumn('arrival_tickets', 'contract_link_time')) {
                $table->dateTime('contract_link_time')->nullable()->after('arrival_purchase_order_id');
            }
        });

        // Copy updated_at into verify_at for verified tickets
        DB::statement("UPDATE arrival_tickets SET verify_at = updated_at WHERE is_ticket_verified = 1 AND updated_at IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_sampling_requests', 'decision_making_time')) {
                $table->dropColumn('decision_making_time');
            }
        });

        Schema::table('arrival_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_tickets', 'verify_at')) {
                $table->dropColumn('verify_at');
            }
            if (Schema::hasColumn('arrival_tickets', 'contract_link_time')) {
                $table->dropColumn('contract_link_time');
            }
        });
    }
};
