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
        Schema::table('sales_inquiries', function (Blueprint $table) {
            $table->enum("contract_type", ["thadda", "pohanch", "x-mill"])->default("thadda")->change();
            $table->decimal('token_money', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_inquiries', function (Blueprint $table) {
            //
        });
    }
};
