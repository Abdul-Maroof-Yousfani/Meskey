<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE `payment_vouchers` MODIFY `module_type` VARCHAR(50) NULL DEFAULT 'raw_material_purchase'");
        } catch (\Exception $e) {
            // Log or ignore if already updated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `payment_vouchers` MODIFY `module_type` ENUM('raw_material_purchase','store_purchase','bill_payment_voucher') NULL DEFAULT 'raw_material_purchase'");
        } catch (\Exception $e) {
            //
        }
    }
};
