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
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_location_id')->nullable()->after('delivery_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->dropColumn('company_location_id');
        });
    }
};
