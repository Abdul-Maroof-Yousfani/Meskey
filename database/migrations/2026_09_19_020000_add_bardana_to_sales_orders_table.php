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
        if (Schema::hasTable('sales_orders') && !Schema::hasColumn('sales_orders', 'is_bardana')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->boolean('is_bardana')->default(0)->after('payment_on_kaanta');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'is_bardana')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('is_bardana');
            });
        }
    }
};
