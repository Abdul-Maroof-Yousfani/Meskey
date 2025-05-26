<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_freights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_purchase_order_id')->constrained();
            $table->date('loading_date');
            $table->string('supplier_name');
            $table->string('broker');
            $table->string('truck_no');
            $table->string('bilty_no');
            $table->foreignId('station_id')->nullable()->constrained();
            $table->integer('no_of_bags');
            $table->foreignId('bag_condition_id')->nullable()->constrained('bag_conditions');
            $table->string('commodity');
            $table->decimal('loading_weight', 10, 2);
            $table->decimal('kanta_charges', 10, 2)->default(0);
            $table->decimal('freight_on_bilty', 10, 2)->default(0);
            $table->decimal('advance_freight', 10, 2)->default(0);
            $table->string('bilty_slip')->nullable();
            $table->string('weighbridge_slip')->nullable();
            $table->string('supplier_bill')->nullable();
            $table->foreignId('company_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_freights');
    }
};
