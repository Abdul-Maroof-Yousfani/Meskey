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
        Schema::dropIfExists('pre_sale_inspections');
        Schema::create('pre_sale_inspections', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('inspection_no', 100)->unique();
            $table->date('date');
            $table->string('party_name');
            $table->string('party_contact_no')->nullable();
            $table->text('reference')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('locations')->nullable();
            $table->json('factories')->nullable();
            $table->json('sections')->nullable();
            $table->foreignId('arrival_location_id')->nullable()->constrained('arrival_locations')->nullOnDelete();
            $table->foreignId('arrival_sub_location_id')->nullable()->constrained('arrival_sub_locations')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_sale_inspections');
    }
};
