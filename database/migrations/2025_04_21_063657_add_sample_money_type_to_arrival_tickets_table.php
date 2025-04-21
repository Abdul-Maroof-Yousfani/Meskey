<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSampleMoneyTypeToArrivalTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            // Replace 'column_before_sample_money' with the actual column before '_sample_money'
            $table->string('sample_money')->after('truck_type_id');
            $table->string('sample_money_type')->after('sample_money');
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('sample_money');
            $table->dropColumn('sample_money_type');
        });
    }
}
