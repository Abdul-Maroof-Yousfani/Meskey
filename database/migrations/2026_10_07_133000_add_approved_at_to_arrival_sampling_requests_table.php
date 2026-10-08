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
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_sampling_requests', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });

        // Copy decision_making_time into approved_at
        DB::statement("UPDATE arrival_sampling_requests SET approved_at = decision_making_time WHERE decision_making_time IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_sampling_requests', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
