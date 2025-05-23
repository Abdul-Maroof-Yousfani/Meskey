<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->string('truck_no_format')->nullable()->after('code');
        });
    }

    public function down()
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropColumn('truck_no_format');
        });
    }
};
