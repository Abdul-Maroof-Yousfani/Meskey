<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->tinyInteger('is_advance_payment')->default(0)->after('status');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('is_advance_payment');
        });
    }
};
