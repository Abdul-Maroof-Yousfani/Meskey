<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('sales_orders', 'so_approval_stage')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->string('so_approval_stage')
                    ->default('stage_1_pending')
                    ->after('am_approval_status')
                    ->comment('stage_1_pending, headoffice_pending, approved, rejected, reverted');
            });

            // Backfill existing records based on current am_approval_status
            DB::table('sales_orders')
                ->where('am_approval_status', 'approved')
                ->update(['so_approval_stage' => 'approved']);

            DB::table('sales_orders')
                ->where('am_approval_status', 'rejected')
                ->update(['so_approval_stage' => 'rejected']);

            DB::table('sales_orders')
                ->where('am_approval_status', 'reverted')
                ->update(['so_approval_stage' => 'reverted']);

            DB::table('sales_orders')
                ->whereNotIn('am_approval_status', ['approved', 'rejected', 'reverted'])
                ->orWhereNull('am_approval_status')
                ->update(['so_approval_stage' => 'stage_1_pending']);
        }

        // Create the Head Office Sales Order approval permission if it does not exist
        Permission::firstOrCreate([
            'name' => 'headoffice-saleorder-approval',
            'guard_name' => 'web'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_orders', 'so_approval_stage')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('so_approval_stage');
            });
        }
    }
};
