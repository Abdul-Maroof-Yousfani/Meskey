<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyArrivalApprovesTable extends Migration
{
    public function up()
    {
        Schema::table('arrival_approves', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            $table->dropColumn('company_id');

            $table->string('gala_name')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('filling_bags_no')->nullable();
            $table->unsignedBigInteger('bag_type_id')->nullable();
            $table->unsignedBigInteger('bag_condition_id')->nullable();
            $table->unsignedBigInteger('bag_packing_id')->nullable();
            $table->enum('bag_packing_approval', ['Half Approved', 'Full Approved'])->nullable();
            $table->integer('total_receivings')->nullable();
            $table->integer('total_bags')->nullable();
            $table->integer('total_rejection')->nullable();
            $table->enum('amanat', ['Yes', 'No'])->default('No');

            $table->text('remark')->nullable()->change();

            $table->foreign('bag_type_id')->references('id')->on('bag_types');
            $table->foreign('bag_condition_id')->references('id')->on('bag_conditions');
            $table->foreign('bag_packing_id')->references('id')->on('bag_packings');
        });
    }

    public function down()
    {
        Schema::table('arrival_approves', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable();

            $table->text('remark')->nullable(false)->change();

            $table->dropForeign(['bag_type_id']);
            $table->dropForeign(['bag_condition_id']);
            $table->dropForeign(['bag_packing_id']);

            $table->dropColumn([
                'gala_name',
                'truck_no',
                'filling_bags_no',
                'bag_type_id',
                'bag_condition_id',
                'bag_packing_id',
                'bag_packing_approval',
                'total_receivings',
                'total_bags',
                'total_rejection',
                'amanat'
            ]);
        });
    }
}
