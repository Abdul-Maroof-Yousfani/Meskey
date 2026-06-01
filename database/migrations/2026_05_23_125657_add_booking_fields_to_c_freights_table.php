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
        Schema::table('c_freights', function (Blueprint $table) {
            $table->string('quantity')->nullable();
            $table->string('shipping_line')->nullable();
            $table->string('t_s')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('c_freights', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'shipping_line', 't_s']);
        });
    }
};
