<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueNoToArrivalSlipsTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_slips', function (Blueprint $table) {
            $table->string('unique_no')->after('id');
        });
    }

    public function down()
    {
        Schema::table('arrival_slips', function (Blueprint $table) {
            $table->dropColumn('unique_no');
        });
    }
}
