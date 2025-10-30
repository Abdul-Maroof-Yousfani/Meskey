<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('deduction_contract_rate_for_freight', 10, 2)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropColumn('deduction_contract_rate_for_freight');
        });
    }
};
