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
        Schema::create('arrival_truck_types', function (Blueprint $table) {
           $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name'); 
            $table->string('description')->nullable(); 
            $table->string('sample_money')->default(0); 
            $table->string('weighbridge_amount')->default(0); 
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes(); 
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_truck_types');
    }
};
