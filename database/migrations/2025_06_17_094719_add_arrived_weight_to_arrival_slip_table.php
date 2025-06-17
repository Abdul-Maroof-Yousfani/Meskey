<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_slips', function (Blueprint $table) {
            $table->integer('arrived_weight')->default(0)->after('creator_id');
        });
    }

    public function down()
    {
        Schema::table('arrival_slips', function (Blueprint $table) {
            $table->dropColumn('arrived_weight');
        });
    }
};
