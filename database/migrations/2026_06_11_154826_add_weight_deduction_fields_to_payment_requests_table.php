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
            $table->decimal('rerate_on_access_weight_kg', 10, 2)->default(0);
            $table->decimal('rerate_on_access_weight_rate', 10, 2)->default(0);
            $table->decimal('rerate_on_access_weight_amount', 10, 2)->default(0);
            // $table->decimal('exempted_weight', 10, 2)->default(0);
            // $table->decimal('billing_weight', 10, 2)->default(0);
            // $table->decimal('access_weight', 10, 2)->default(0);


            // $table->decimal('deduction_on_weight_difference_kg', 10, 2)->default(0);
            // $table->decimal('deduction_on_weight_difference_amount', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'rerate_on_access_weight_kg',
                'rerate_on_access_weight_rate',
                'rerate_on_access_weight_amount',
                // 'exempted_weight',
                // 'billing_weight',
                // 'access_weight',
            ]);
        });
    }
};