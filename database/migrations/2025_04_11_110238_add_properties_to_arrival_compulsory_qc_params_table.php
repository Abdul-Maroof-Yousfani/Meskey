<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPropertiesToArrivalCompulsoryQcParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('arrival_compulsory_qc_params', function (Blueprint $table) {
            $table->json('properties')
                ->nullable()
                ->after('options')
                ->comment('Additional properties in JSON format');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('arrival_compulsory_qc_params', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
    }
}
