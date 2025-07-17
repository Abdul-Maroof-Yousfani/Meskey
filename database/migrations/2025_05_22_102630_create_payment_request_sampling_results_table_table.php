<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_request_sampling_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_data_id')->constrained()->onDelete('cascade');
            $table->foreignId('slab_type_id')->nullable()->constrained('product_slab_types')->onDelete('set null');
            $table->string('name');
            $table->decimal('checklist_value', 15, 2);
            $table->decimal('suggested_deduction', 15, 2);
            $table->decimal('applied_deduction', 15, 2);
            $table->string('deduction_type');
            $table->decimal('deduction_amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_request_sampling_results');
    }
};
