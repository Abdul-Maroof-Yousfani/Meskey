<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. payment_requests
        if (!Schema::hasColumn('payment_requests', 'delivery_challan_id')) {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_challan_id')->nullable()->after('payment_request_data_id');
                $table->foreign('delivery_challan_id')->references('id')->on('delivery_challans')->nullOnDelete();
            });
        }

        // Expand module_type & request_type from restrictive enums to VARCHAR(50)
        try {
            DB::statement("ALTER TABLE `payment_requests` MODIFY `module_type` VARCHAR(50) NULL DEFAULT 'purchase_order'");
        } catch (\Exception $e) {
            // Ignore if already varchar
        }

        try {
            DB::statement("ALTER TABLE `payment_requests` MODIFY `request_type` VARCHAR(50) NULL DEFAULT 'payment'");
        } catch (\Exception $e) {
            // Ignore if already varchar
        }

        // 2. payment_request_datas
        if (!Schema::hasColumn('payment_request_datas', 'delivery_challan_id')) {
            Schema::table('payment_request_datas', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_challan_id')->nullable()->after('ticket_id');
                $table->foreign('delivery_challan_id')->references('id')->on('delivery_challans')->nullOnDelete();
            });
        }

        try {
            DB::statement("ALTER TABLE `payment_request_datas` MODIFY `module_type` VARCHAR(50) NULL DEFAULT 'purchase_order'");
        } catch (\Exception $e) {
            // Ignore if already varchar
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_requests', 'delivery_challan_id')) {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->dropForeign(['delivery_challan_id']);
                $table->dropColumn('delivery_challan_id');
            });
        }

        if (Schema::hasColumn('payment_request_datas', 'delivery_challan_id')) {
            Schema::table('payment_request_datas', function (Blueprint $table) {
                $table->dropForeign(['delivery_challan_id']);
                $table->dropColumn('delivery_challan_id');
            });
        }
    }
};
