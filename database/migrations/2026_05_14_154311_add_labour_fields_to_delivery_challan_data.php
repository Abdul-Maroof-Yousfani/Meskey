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
        Schema::table('delivery_challan_data', function (Blueprint $table) {
            $table->decimal('labour_rate', 10, 2)->default(0)->after('rate');
            $table->decimal('labour_amount', 15, 2)->default(0)->after('labour_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_challan_data', function (Blueprint $table) {
            $table->dropColumn(['labour_rate', 'labour_amount']);
        });
    }
};
