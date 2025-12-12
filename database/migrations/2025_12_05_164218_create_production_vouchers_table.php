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
        Schema::create('production_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prod_no')->unique();
            $table->date('prod_date');
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('location_id'); // company_location_id
            $table->decimal('produced_qty_kg', 15, 2)->default(0);
            $table->unsignedBigInteger('supervisor_id')->nullable(); // user_id
            $table->decimal('labor_cost_per_kg', 10, 4)->default(0);
            $table->decimal('overhead_cost_per_kg', 10, 4)->default(0);
            $table->enum('status', ['draft', 'completed', 'approved'])->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_vouchers');
    }
};
