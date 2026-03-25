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
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->decimal('rate_per_maund', 15, 2)->default(0)->after('rate');
            $table->decimal('maunds', 15, 3)->default(0)->after('metric_tons');
            $table->decimal('stuffing_maunds', 15, 3)->default(0)->after('stuffing_in_container');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->dropColumn(['rate_per_maund', 'maunds', 'stuffing_maunds']);
        });
    }
};
