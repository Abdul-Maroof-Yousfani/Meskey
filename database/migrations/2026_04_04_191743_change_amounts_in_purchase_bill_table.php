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
        Schema::table('purchase_bills_data', function (Blueprint $table) {
            $table->decimal("qty", 12, 3)->change(); // supports large qty + decimals
            $table->decimal("rate", 15, 2)->change();

            $table->decimal("gross_amount", 15, 2)->default(0)->change();
            $table->decimal("net_amount", 15, 2)->default(0)->change();

            $table->decimal("tax_percent", 5, 2)->default(0)->change();
            $table->decimal("tax_amount", 15, 2)->default(0)->change();

            $table->decimal("discount_percent", 5, 2)->default(0)->change();
            $table->decimal("discount_amount", 15, 2)->default(0)->change();

            $table->decimal("deduction", 15, 2)->default(0)->change();
            $table->decimal("deduction_per_piece", 15, 2)->default(0)->change();

            $table->decimal("final_amount", 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bill', function (Blueprint $table) {
            //
        });
    }
};
