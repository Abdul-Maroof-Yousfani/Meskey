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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('payment_on_kaanta')->default(false)->after('am_approval_status');
        });

        Schema::table('delivery_order', function (Blueprint $table) {
            $table->boolean('is_auto_created_from_so')->default(false)->after('am_approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('payment_on_kaanta');
        });

        Schema::table('delivery_order', function (Blueprint $table) {
            $table->dropColumn('is_auto_created_from_so');
        });
    }
};
