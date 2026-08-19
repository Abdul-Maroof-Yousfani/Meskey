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
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->date('arrived_date')->nullable()->after('dc_date');
            $table->decimal('arrived_weight', 15, 2)->default(0)->after('weighbridge_amount');
            $table->decimal('exempted_weight', 15, 2)->default(0)->after('arrived_weight');
            $table->decimal('payment_weight', 15, 2)->default(0)->after('exempted_weight');
        });

        Schema::table('receiving_request_items', function (Blueprint $table) {
            $table->integer('no_of_bags')->default(0)->after('remaining_amount');
            $table->decimal('unloading_labour_rate', 15, 2)->default(0)->after('no_of_bags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_request_items', function (Blueprint $table) {
            $table->dropColumn(['no_of_bags', 'unloading_labour_rate']);
        });

        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->dropColumn(['arrived_date', 'arrived_weight', 'exempted_weight', 'payment_weight']);
        });
    }
};
