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
        Schema::create('purchase_against_job_orders', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('purchase_request_id'); // Foreign key
            $table->unsignedBigInteger('purchase_request_data_id'); // Foreign key
            $table->unsignedBigInteger('job_order_id'); // Foreign key
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('purchase_request_data_id')
            ->references('id')->on('purchase_request_data')
            ->onDelete('cascade'); // Cascade on hard delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_against_job_orders');
    }
};
