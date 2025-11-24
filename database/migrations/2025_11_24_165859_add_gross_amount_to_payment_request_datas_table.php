<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('gross_amount', 15, 2)
                ->nullable()
                ->after('loading_weighbridge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn('gross_amount');
        });
    }

};
