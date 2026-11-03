<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('production_analysis_requests')) {
            Schema::table('production_analysis_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('production_analysis_requests', 'is_auto_generated')) {
                    $table->boolean('is_auto_generated')->default(false)->after('status')->index();
                }
                if (!Schema::hasColumn('production_analysis_requests', 'auto_generated_at')) {
                    $table->timestamp('auto_generated_at')->nullable()->after('is_auto_generated')->index();
                }
                if (!Schema::hasColumn('production_analysis_requests', 'auto_scheduled_interval')) {
                    $table->unsignedInteger('auto_scheduled_interval')->nullable()->after('auto_generated_at');
                }
                if (!Schema::hasColumn('production_analysis_requests', 'machine_plan_setting_id')) {
                    $table->unsignedBigInteger('machine_plan_setting_id')->nullable()->after('production_voucher_id')->index();
                }
            });

            // Backfill existing auto generated requests
            DB::table('production_analysis_requests')
                ->where('remarks', 'LIKE', '%Auto-generated%')
                ->orWhere('remarks', 'LIKE', '%Auto-create%')
                ->update([
                    'is_auto_generated' => true,
                    'auto_generated_at' => DB::raw('created_at'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('production_analysis_requests')) {
            Schema::table('production_analysis_requests', function (Blueprint $table) {
                $columns = ['is_auto_generated', 'auto_generated_at', 'auto_scheduled_interval', 'machine_plan_setting_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('production_analysis_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
