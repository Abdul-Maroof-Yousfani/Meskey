<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->decimal('bank_charges_for_gate_buying', 10, 2)
                ->default(0)
                ->after('city_id')
                ->comment('Bank charges for gate buying. Default 0.00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropColumn('bank_charges_for_gate_buying');
        });
    }
};
