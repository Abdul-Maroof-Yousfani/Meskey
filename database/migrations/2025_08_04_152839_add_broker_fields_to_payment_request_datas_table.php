<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_id')->nullable()->after('bag_rate_amount');
            $table->decimal('brokery_amount', 15, 2)->nullable()->after('broker_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn(['broker_id', 'brokery_amount']);
        });
    }
};
