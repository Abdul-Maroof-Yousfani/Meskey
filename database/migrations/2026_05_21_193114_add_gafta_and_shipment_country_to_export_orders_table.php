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
            $table->unsignedBigInteger('gafta_id')->nullable();
            $table->foreign('gafta_id')->references('id')->on('gaftas')->nullOnDelete();

            $table->unsignedBigInteger('shipment_country_id')->nullable();
            $table->foreign('shipment_country_id')->references('id')->on('shipment_countries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn(['gafta_id', 'shipment_country_id']);
        });
    }
};
