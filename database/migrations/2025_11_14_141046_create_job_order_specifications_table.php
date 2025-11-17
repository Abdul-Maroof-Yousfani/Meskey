<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_order_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_slab_type_id')->constrained()->onDelete('cascade');
            $table->string('spec_name');
            $table->string('spec_value');
            $table->enum('value_type', ['min', 'max'])->default('min');
            $table->string('uom')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_order_specifications');
    }
};