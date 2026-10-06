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
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_sale_inspections', 'am_approval_status')) {
                $table->string('am_approval_status', 50)->default('pending')->after('status');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'am_change_made')) {
                $table->tinyInteger('am_change_made')->default(1)->after('am_approval_status');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'is_completed')) {
                $table->boolean('is_completed')->default(0)->after('am_change_made');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('is_completed');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'confirmation_approval_status')) {
                $table->string('confirmation_approval_status', 50)->nullable()->after('completed_by');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'confirmation_am_change_made')) {
                $table->tinyInteger('confirmation_am_change_made')->default(1)->after('confirmation_approval_status');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'vehicle_no')) {
                $table->string('vehicle_no', 100)->nullable()->after('confirmation_am_change_made');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'vehicle_assigned_at')) {
                $table->timestamp('vehicle_assigned_at')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('pre_sale_inspections', 'vehicle_assigned_by')) {
                $table->foreignId('vehicle_assigned_by')->nullable()->after('vehicle_assigned_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $cols = [
                'vehicle_assigned_by',
                'vehicle_assigned_at',
                'vehicle_no',
                'confirmation_am_change_made',
                'confirmation_approval_status',
                'completed_by',
                'completed_at',
                'is_completed',
                'am_change_made',
                'am_approval_status',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('pre_sale_inspections', $col)) {
                    if (in_array($col, ['completed_by', 'vehicle_assigned_by'])) {
                        $table->dropForeign([$col]);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
