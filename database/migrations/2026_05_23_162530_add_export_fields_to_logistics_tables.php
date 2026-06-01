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
        Schema::table('logistics', function (Blueprint $table) {
            $table->string('job_order')->nullable();
            $table->string('return_port')->nullable();
            $table->string('booking_no')->nullable();
            $table->string('shipping_line')->nullable();
        });

        Schema::table('logistics_items', function (Blueprint $table) {
            $table->string('brand')->nullable();
            $table->string('packing_size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics', function (Blueprint $table) {
            $table->dropColumn(['job_order', 'return_port', 'booking_no', 'shipping_line']);
        });

        Schema::table('logistics_items', function (Blueprint $table) {
            $table->dropColumn(['brand', 'packing_size']);
        });
    }
};
