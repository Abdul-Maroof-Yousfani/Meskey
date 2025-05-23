<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->tinyInteger('truck_no_format')->default(0)->nullable()->after('code');
        });
    }

    public function down()
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropColumn('truck_no_format');
        });
    }
};
