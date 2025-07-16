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
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('company_id');

            // $table->foreign('location_id')
            //     ->references('id')
            //     ->on('locations')
            //     ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            // $table->dropForeign(['location_id']);

            $table->dropColumn('location_id');
        });
    }
};
