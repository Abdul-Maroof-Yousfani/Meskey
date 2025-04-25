<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('station_name');
            $table->after('bags', function ($table) {
                $table->foreignId('station_id')->nullable()->constrained('stations');
            });
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->dropColumn('station_id');
            $table->string('station_name')->nullable()->after('bags');
        });
    }
};
