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
        Schema::create('arrival_qc_relief_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('slab_type_id')->nullable();
            $table->string('parameter_name');
            $table->string('relief_type')->default('fixed'); // 'fixed' or 'minus_amount'
            $table->decimal('from_range', 8, 2)->default(0);
            $table->decimal('to_range', 8, 2)->default(0);
            $table->decimal('fixed_value', 8, 2)->default(0);
            $table->decimal('relief_value', 8, 2)->default(0);
            $table->decimal('relief_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_qc_relief_parameters');
    }
};
