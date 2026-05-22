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
            $table->dropForeign(['shipment_country_id']);
            $table->dropColumn('shipment_country_id');
            $table->json('shipment_country')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn('shipment_country');
            $table->unsignedBigInteger('shipment_country_id')->nullable();
            $table->foreign('shipment_country_id')->references('id')->on('shipment_countries')->nullOnDelete();
        });
    }
};
