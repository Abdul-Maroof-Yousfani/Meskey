<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBagWeightColumnInArrivalPurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->decimal('bag_weight', 10, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->integer('bag_weight')->change();
        });
    }
}
