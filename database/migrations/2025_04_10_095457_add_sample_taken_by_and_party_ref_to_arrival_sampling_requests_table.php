<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->string('party_ref_no')->nullable()->after('is_resampling_made');

            $table->unsignedBigInteger('sample_taken_by')->nullable()->after('party_ref_no');

            $table->foreign('sample_taken_by')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropForeign(['sample_taken_by']);

            $table->dropColumn(['party_ref_no', 'sample_taken_by']);
        });
    }
};
