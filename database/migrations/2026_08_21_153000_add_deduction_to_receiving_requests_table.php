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
            $table->decimal('transporter_deduction', 15, 2)->default(0)->after('transporter_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->dropColumn('transporter_deduction');
        });
    }
};
