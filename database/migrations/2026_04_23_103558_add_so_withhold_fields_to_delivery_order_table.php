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
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->decimal('so_withhold_percentage', 5, 2)->nullable()->default(10.00)->after('ref_no');
            $table->decimal('so_held_amount', 15, 2)->nullable()->default(0)->after('so_withhold_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->dropColumn(['so_withhold_percentage', 'so_held_amount']);
        });
    }
};
