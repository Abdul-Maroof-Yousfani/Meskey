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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('other_deduction_kg', 10, 4)->nullable()->after('request_type');
            $table->decimal('other_deduction_value', 10, 4)->nullable()->after('other_deduction_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['other_deduction_kg', 'other_deduction_value']);
        });
    }
};
