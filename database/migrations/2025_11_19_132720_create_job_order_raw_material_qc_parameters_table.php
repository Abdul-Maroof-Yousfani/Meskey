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
            $table->foreignId('job_order__qc_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_slab_type_id')->constrained()->onDelete('cascade');
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