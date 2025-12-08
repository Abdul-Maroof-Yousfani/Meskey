<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('job_order_raw_material_qc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_rm_qc_id')
                ->constrained('job_order_raw_material_qcs')
                ->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('arrival_sub_location_id')->constrained();
            $table->boolean('fumigation_required')->default(false);
            $table->decimal('suggested_quantity', 12, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_order_raw_material_qc_items');
    }
};