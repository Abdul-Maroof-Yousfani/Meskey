<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('job_order_raw_material_qcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('qc_no')->unique();
            $table->date('qc_date');
            $table->foreignId('job_order_id')->constrained();
            $table->foreignId('location_id')->constrained('company_locations');
            $table->string('mill')->nullable();
            $table->json('commodities');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_order_raw_material_qcs');
    }
};