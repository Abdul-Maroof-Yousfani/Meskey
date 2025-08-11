<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->float('closing_trucks_qty', 8, 2)->default(0.00)->change();
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->integer('closing_trucks_qty')->default(0)->change();
        });
    }
};
