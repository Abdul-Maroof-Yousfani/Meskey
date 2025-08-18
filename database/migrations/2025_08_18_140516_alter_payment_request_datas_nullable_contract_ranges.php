<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('min_contract_range', 15, 2)->nullable()->change();
            $table->decimal('max_contract_range', 15, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->decimal('min_contract_range', 15, 2)->default(0)->change();
            $table->decimal('max_contract_range', 15, 2)->default(0)->change();
        });
    }
};
