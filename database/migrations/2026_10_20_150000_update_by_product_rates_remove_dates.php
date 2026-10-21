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
        Schema::table('by_product_rates', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
            $table->date('from_date')->nullable()->change();
            $table->date('to_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('by_product_rates', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->change();
            $table->date('from_date')->nullable(false)->change();
            $table->date('to_date')->nullable(false)->change();
        });
    }
};
