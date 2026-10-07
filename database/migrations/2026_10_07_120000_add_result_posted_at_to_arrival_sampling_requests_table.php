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
            if (!Schema::hasColumn('arrival_sampling_requests', 'result_posted_at')) {
                $table->dateTime('result_posted_at')->nullable()->after('done_by');
            }
        });

        // Update result_posted_at from related arrival_sampling_results created_at
        DB::statement("
            UPDATE arrival_sampling_requests asr
            INNER JOIN (
                SELECT arrival_sampling_request_id, MIN(created_at) AS result_created_at
                FROM arrival_sampling_results
                WHERE created_at IS NOT NULL
                GROUP BY arrival_sampling_request_id
            ) res ON asr.id = res.arrival_sampling_request_id
            SET asr.result_posted_at = res.result_created_at
        ");

        // Fallback for any requests that have compulsory results if still null
        DB::statement("
            UPDATE arrival_sampling_requests asr
            INNER JOIN (
                SELECT arrival_sampling_request_id, MIN(created_at) AS result_created_at
                FROM arrival_sampling_results_for_compulsury
                WHERE created_at IS NOT NULL
                GROUP BY arrival_sampling_request_id
            ) res ON asr.id = res.arrival_sampling_request_id
            SET asr.result_posted_at = res.result_created_at
            WHERE asr.result_posted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_sampling_requests', 'result_posted_at')) {
                $table->dropColumn('result_posted_at');
            }
        });
    }
};
