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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('no_of_filling_bags')->default(0);
            $table->decimal('filling_bag_rate', 10, 2)->default(0);
            $table->decimal('filling_bag_amount', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'no_of_filling_bags',
                'filling_bag_rate',
                'filling_bag_amount',
            ]);
        });
    }
};