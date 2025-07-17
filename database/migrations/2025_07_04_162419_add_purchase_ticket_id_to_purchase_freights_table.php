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
        Schema::table('purchase_freights', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_ticket_id')->after('arrival_purchase_order_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('purchase_freights', function (Blueprint $table) {
            $table->dropColumn('purchase_ticket_id');
        });
    }
};
