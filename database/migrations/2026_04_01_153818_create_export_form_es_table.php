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
        Schema::create('export_form_es', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_order_id')->nullable()->constrained('export_orders')->onDelete('cascade');
            $table->foreignId('buyer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('job_order_id')->nullable()->constrained('job_orders')->onDelete('set null');
            $table->string('form_e_no')->unique()->nullable();
            $table->date('form_e_date')->nullable();
            $table->string('attachment')->nullable();
            $table->double('total_quantity', 15, 2)->nullable();
            $table->double('remaining_quantity', 15, 2)->nullable();
            $table->double('input_quantity', 15, 2)->nullable();
            $table->json('export_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_form_es');
    }
};
