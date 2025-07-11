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
            $table->decimal('bag_weight', 10, 2)->after('bags')->default(0)->nullable();
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('bag_weight');
        });
    }
};
