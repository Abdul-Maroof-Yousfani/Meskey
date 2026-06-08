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
        Schema::table('purchase_return_data', function (Blueprint $table) {
            $table->decimal('deduction', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('deduction_per_piece', 15, 2)->default(0)->after('deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_data', function (Blueprint $table) {
            $table->dropColumns(['deduction', 'deduction_per_piece']);
        });
    }
};
