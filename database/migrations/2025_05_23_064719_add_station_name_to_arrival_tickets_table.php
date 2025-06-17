<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->string('station_name')->nullable()->after('station_id');
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('station_name');
        });
    }
};
