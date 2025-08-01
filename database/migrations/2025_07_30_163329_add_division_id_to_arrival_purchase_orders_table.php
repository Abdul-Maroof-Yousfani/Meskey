<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('weighbridge_from');
            $table->foreign('division_id')->references('id')->on('divisions');
        });
    }

    public function down()
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
