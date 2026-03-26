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
        Schema::create('quotation_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->foreignId('product_slab_type_id')->constrained('product_slab_types')->onDelete('cascade');
            $table->string('spec_name');
            $table->string('spec_value')->nullable();
            $table->string('uom')->nullable();
            $table->enum('value_type', ['min', 'max'])->default('min');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_specifications');
    }
};
