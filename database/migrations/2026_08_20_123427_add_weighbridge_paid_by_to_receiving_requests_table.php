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
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->string('weighbridge_paid_by', 50)->nullable()->after('weighbridge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->dropColumn('weighbridge_paid_by');
        });
    }
};
