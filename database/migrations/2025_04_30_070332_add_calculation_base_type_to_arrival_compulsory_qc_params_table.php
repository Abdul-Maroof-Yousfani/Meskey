<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalculationBaseTypeToArrivalCompulsoryQcParamsTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_compulsory_qc_params', function (Blueprint $table) {
            $table->tinyInteger('calculation_base_type')
                ->default(3)
                ->nullable()
                ->after('properties')
                ->comment('1 = Percentage, 2 = KG, 3 = Price, 4 = Quantity, etc...');
        });
    }

    public function down()
    {
        Schema::table('arrival_compulsory_qc_params', function (Blueprint $table) {
            $table->dropColumn('calculation_base_type');
        });
    }
}
