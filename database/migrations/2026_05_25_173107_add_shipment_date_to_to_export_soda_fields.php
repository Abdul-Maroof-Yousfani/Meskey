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
        Schema::table('export_soda_fields', function (Blueprint $table) {
            $table->date('shipment_date_to')->nullable()->after('shipment_date_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_soda_fields', function (Blueprint $table) {
            $table->dropColumn('shipment_date_to');
        });
    }
};
