<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('username')->unique()->after('name');
            // $table->dropColumn('email');
            // $table->enum('user_type', ['super-admin', 'user'])->default('user'); 
            // $table->unsignedBigInteger('company_location_id')->nullable()->change();

            $table->unsignedBigInteger('arrival_location_id')->nullable()->after('company_location_id');

            $table->foreign('arrival_location_id')
                ->references('id')
                ->on('arrival_locations')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['arrival_location_id']);
            $table->dropColumn('arrival_location_id');
            // $table->dropColumn('username');
            // $table->string('email')->unique();
            // $table->dropColumn('user_type');
            // $table->unsignedBigInteger('company_location_id')->nullable(false)->change();
        });
    }
};
