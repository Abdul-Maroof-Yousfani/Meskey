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
        Schema::table('plants', function (Blueprint $table) {
            if (!Schema::hasColumn('plants', 'auto_analysis_enabled')) {
                $table->boolean('auto_analysis_enabled')->default(false)->after('status');
            }
            if (!Schema::hasColumn('plants', 'min_analysis_interval')) {
                $table->unsignedInteger('min_analysis_interval')->default(20)->after('auto_analysis_enabled');
            }
            if (!Schema::hasColumn('plants', 'max_analysis_interval')) {
                $table->unsignedInteger('max_analysis_interval')->default(60)->after('min_analysis_interval');
            }
            if (!Schema::hasColumn('plants', 'input_min_interval')) {
                $table->unsignedInteger('input_min_interval')->nullable()->after('max_analysis_interval');
            }
            if (!Schema::hasColumn('plants', 'input_max_interval')) {
                $table->unsignedInteger('input_max_interval')->nullable()->after('input_min_interval');
            }
            if (!Schema::hasColumn('plants', 'output_min_interval')) {
                $table->unsignedInteger('output_min_interval')->nullable()->after('input_max_interval');
            }
            if (!Schema::hasColumn('plants', 'output_max_interval')) {
                $table->unsignedInteger('output_max_interval')->nullable()->after('output_min_interval');
            }
            if (!Schema::hasColumn('plants', 'next_input_analysis_at')) {
                $table->timestamp('next_input_analysis_at')->nullable()->after('output_max_interval');
            }
            if (!Schema::hasColumn('plants', 'next_output_analysis_at')) {
                $table->timestamp('next_output_analysis_at')->nullable()->after('next_input_analysis_at');
            }
            if (!Schema::hasColumn('plants', 'require_last_completed')) {
                $table->boolean('require_last_completed')->default(false)->after('next_output_analysis_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $columns = [
                'auto_analysis_enabled',
                'min_analysis_interval',
                'max_analysis_interval',
                'input_min_interval',
                'input_max_interval',
                'output_min_interval',
                'output_max_interval',
                'next_input_analysis_at',
                'next_output_analysis_at',
                'require_last_completed',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('plants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
