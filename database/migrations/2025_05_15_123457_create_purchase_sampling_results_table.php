<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_sampling_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_sampling_request_id');
            $table->unsignedBigInteger('product_slab_type_id');
            $table->string('checklist_value')->nullable();
            $table->string('suggested_deduction')->nullable();
            $table->string('applied_deduction')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('purchase_sampling_request_id')->references('id')->on('purchase_sampling_requests')->onDelete('cascade');
            $table->foreign('product_slab_type_id')->references('id')->on('product_slab_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_sampling_results');
    }
};
