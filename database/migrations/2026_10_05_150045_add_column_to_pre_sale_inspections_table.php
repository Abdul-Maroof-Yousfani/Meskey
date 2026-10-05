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
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $table->string('visitor_name')->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_sale_inspections', function (Blueprint $table) {
            $table->dropColumn('visitor_name');
        });
    }
};
