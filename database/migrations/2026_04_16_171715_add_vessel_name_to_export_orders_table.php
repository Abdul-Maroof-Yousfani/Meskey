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
            $table->string('vessel_name')->after('shipment_delivery_date_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn('vessel_name');
        });
    }
};
