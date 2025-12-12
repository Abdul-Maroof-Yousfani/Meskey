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
            $table->unsignedBigInteger('job_order_id')->nullable(); // Kept for backward compatibility
            $table->unsignedBigInteger('location_id'); // company_location_id
            $table->unsignedBigInteger('product_id')->nullable(); // Commodity
            $table->decimal('produced_qty_kg', 15, 2)->default(0);
            $table->unsignedBigInteger('supervisor_id')->nullable(); // user_id
            $table->decimal('labor_cost_per_kg', 10, 4)->default(0);
            $table->decimal('overhead_cost_per_kg', 10, 4)->default(0);
            $table->enum('status', ['draft', 'completed', 'approved'])->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Create pivot table for many-to-many relationship between production vouchers and job orders
        Schema::create('production_voucher_job_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_voucher_id');
            $table->unsignedBigInteger('job_order_id');
            $table->timestamps();

            $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->onDelete('cascade');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            
            // Ensure unique combination (using shorter name to avoid MySQL limit)
            $table->unique(['production_voucher_id', 'job_order_id'], 'pv_jo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_voucher_job_orders');
        Schema::dropIfExists('production_vouchers');
    }
};
