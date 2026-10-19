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
        Schema::table('milling_rates', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('plant_id');
            $table->date('from_date')->nullable()->after('title');
            $table->date('to_date')->nullable()->after('from_date');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milling_rates', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'from_date', 'to_date']);
        });
    }
};
