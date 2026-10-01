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
        if (!Schema::hasColumn('sales_inquiries', 'si_approval_stage')) {
            Schema::table('sales_inquiries', function (Blueprint $table) {
                $table->string('si_approval_stage')->nullable()->default('stage_1_pending')->after('am_change_made');
            });
        }

        $salesParent = \Spatie\Permission\Models\Permission::where('name', 'sales')->first();

        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'headoffice-sale-inquiry-approval', 'guard_name' => 'web'],
            ['parent_id' => $salesParent?->id]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_inquiries', 'si_approval_stage')) {
            Schema::table('sales_inquiries', function (Blueprint $table) {
                $table->dropColumn('si_approval_stage');
            });
        }

        \Spatie\Permission\Models\Permission::whereIn('name', [
            'headoffice-sale-inquiry-approval'
        ])->delete();
    }
};
