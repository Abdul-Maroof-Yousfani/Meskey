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
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->string('comparison_code')->nullable()->after('purchase_quotation_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->dropColumn('comparison_code');
        });
    }
};
