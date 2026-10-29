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
            $table->integer('positive_tolerance')->nullable()->after('voucher_heading');
            $table->integer('negative_tolerance')->nullable()->after('positive_tolerance');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn('positive_tolerance');
            $table->dropColumn('negative_tolerance');
        });
    }
};
