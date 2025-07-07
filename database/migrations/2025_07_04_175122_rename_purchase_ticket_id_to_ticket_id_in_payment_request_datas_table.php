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
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->renameColumn('arrival_ticket_id', 'purchase_ticket_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->renameColumn('purchase_ticket_id', 'arrival_ticket_id');
        });
    }
};
