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
        Schema::table('export_orders', function (Blueprint $table) {
            $table->text('confidentiality')->nullable();
            $table->string('discharge_rate')->nullable();
            $table->string('discharge_shex_eiu')->nullable();
            $table->string('minimum_daily_rate')->nullable();
            $table->string('minimum_daily_shex_eiu')->nullable();
            $table->string('fob_account')->nullable(); // 'ON BUYER ACCOUNT' or 'ON SELLER ACCOUNT'
            $table->json('fumigation_by')->nullable();
            $table->json('inspection_by')->nullable();
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            if (Schema::hasColumn('export_order_packing_items', 'fumigation_company_id')) {
                $table->dropColumn('fumigation_company_id');
            }
            if (Schema::hasColumn('export_order_packing_items', 'inspection_by')) {
                $table->dropColumn('inspection_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn([
                'confidentiality',
                'discharge_rate',
                'discharge_shex_eiu',
                'minimum_daily_rate',
                'minimum_daily_shex_eiu',
                'fob_account',
                'fumigation_by',
                'inspection_by',
            ]);
        });

        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->json('fumigation_company_id')->nullable();
            $table->json('inspection_by')->nullable();
        });
    }
};
