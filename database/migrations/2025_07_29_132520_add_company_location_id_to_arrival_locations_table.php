<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('arrival_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('company_location_id')->nullable()->after('id');

            $table->foreign('company_location_id')
                ->references('id')
                ->on('company_locations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('arrival_locations', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);
            $table->dropColumn('company_location_id');
        });
    }
};
