<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_order_raw_material_qc_parameters', function (Blueprint $table) {
            $table->id();

            // Short foreign key name
            $table->unsignedBigInteger('job_order_qc_item_id');
            $table->foreign('job_order_qc_item_id', 'jrmp_qc_item_fk')
                ->references('id')
                ->on('job_order_raw_material_qc_items')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_slab_type_id');
            $table->foreign('product_slab_type_id', 'jrmp_slab_type_fk')
                ->references('id')
                ->on('product_slab_types')
                ->onDelete('cascade');

            $table->string('parameter_name');
            $table->decimal('parameter_value', 8, 4);
            $table->string('uom')->default('%');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_order_raw_material_qc_parameters');
    }
};
