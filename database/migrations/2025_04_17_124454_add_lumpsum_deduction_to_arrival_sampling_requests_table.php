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
            $table->decimal('lumpsum_deduction', 10, 2)
                ->default(0)
                ->after('sample_taken_by');

            $table->tinyInteger('is_lumpsum_deduction')
                ->default(0)
                ->after('lumpsum_deduction')
                ->comment('Switch state for lumpsum deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropColumn(['lumpsum_deduction', 'is_lumpsum_deduction']);
        });
    }
};
