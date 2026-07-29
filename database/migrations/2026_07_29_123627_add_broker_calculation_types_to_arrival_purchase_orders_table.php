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
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->string('broker_one_calculation_type')->nullable()->after('broker_one_commission');
            $table->string('broker_two_calculation_type')->nullable()->after('broker_two_commission');
            $table->string('broker_three_calculation_type')->nullable()->after('broker_three_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['broker_one_calculation_type', 'broker_two_calculation_type', 'broker_three_calculation_type']);
        });
    }
};
