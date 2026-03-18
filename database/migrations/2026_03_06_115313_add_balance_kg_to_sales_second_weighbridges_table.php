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
        Schema::table('sales_second_weighbridges', function (Blueprint $table) {
            $table->decimal('balance_kg', 12, 2)->nullable()->after('net_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_second_weighbridges', function (Blueprint $table) {
            $table->dropColumn('balance_kg');
        });
    }
};
