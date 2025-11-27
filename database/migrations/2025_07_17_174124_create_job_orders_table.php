<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('job_order_no')->unique();
            $table->date('job_order_date');
            // $table->foreignId('company_location_id')->nullable()->constrained('company_locations');
            $table->string('ref_no')->nullable();
            $table->json('attention_to')->nullable(); // JSON for multiple users
            $table->foreignId('product_id')->constrained();
            $table->foreignId('crop_year_id')->nullable()->constrained('crop_years')->onDelete('set null');
            $table->longText('other_specifications')->nullable();
            $table->text('remarks')->nullable();
            $table->text('order_description')->nullable();
            $table->json('inspection_company_id')->nullable(); // JSON for multiple inspection companies
            // $table->json('fumigation_company_id')->nullable(); // JSON for multiple fumigation companies
            // $table->date('delivery_date')->nullable();
            $table->date('loading_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('arrival_locations')->nullable(); // JSON for multiple arrival locations
            $table->text('packing_description')->nullable();




            // Add long text for other specifications

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
