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
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->decimal('lumpsum_deduction_maund', 10, 2)->default(0)->after('lumpsum_deduction');
            $table->decimal('lumpsum_deduction_kgs_maund', 10, 2)->default(0)->after('lumpsum_deduction_kgs');
        });

        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->decimal('lumpsum_deduction_maund', 10, 2)->default(0)->after('lumpsum_deduction');
            $table->decimal('lumpsum_deduction_kgs_maund', 10, 2)->default(0)->after('lumpsum_deduction_kgs');
        });

        Schema::table('arrival_sampling_results', function (Blueprint $table) {
            $table->string('applied_deduction_maund')->nullable()->after('applied_deduction');
        });

        Schema::table('arrival_sampling_results_for_compulsury', function (Blueprint $table) {
            $table->string('applied_deduction_maund')->nullable()->after('applied_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropColumn(['lumpsum_deduction_maund', 'lumpsum_deduction_kgs_maund']);
        });

        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn(['lumpsum_deduction_maund', 'lumpsum_deduction_kgs_maund']);
        });

        Schema::table('arrival_sampling_results', function (Blueprint $table) {
            $table->dropColumn('applied_deduction_maund');
        });

        Schema::table('arrival_sampling_results_for_compulsury', function (Blueprint $table) {
            $table->dropColumn('applied_deduction_maund');
        });
    }
};
