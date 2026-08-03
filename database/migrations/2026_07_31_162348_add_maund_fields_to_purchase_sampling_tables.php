<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_sampling_requests', 'lumpsum_deduction_maund')) {
            Schema::table('purchase_sampling_requests', function (Blueprint $table) {
                $table->decimal('lumpsum_deduction_maund', 10, 2)->default(0)->after('lumpsum_deduction');
                $table->decimal('lumpsum_deduction_kgs_maund', 10, 2)->default(0)->after('lumpsum_deduction_kgs');
            });
        }

        if (!Schema::hasColumn('purchase_sampling_results', 'applied_deduction_maund')) {
            Schema::table('purchase_sampling_results', function (Blueprint $table) {
                $table->string('applied_deduction_maund')->nullable()->after('applied_deduction');
            });
        }

        if (!Schema::hasColumn('purchase_sampling_results_for_compulsury', 'applied_deduction_maund')) {
            Schema::table('purchase_sampling_results_for_compulsury', function (Blueprint $table) {
                $table->string('applied_deduction_maund')->nullable()->after('applied_deduction');
            });
        }
    }

    public function down(): void
    {
        // skip down
    }
};
