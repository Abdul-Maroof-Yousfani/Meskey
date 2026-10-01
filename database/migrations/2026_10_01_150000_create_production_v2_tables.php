<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Migration for Production V2
 * Tables:
 * 1. production_job_orders
 * 2. production_phase1 (Drying)
 * 3. production_phase2 (Steaming / Parboiling)
 * 4. production_phase3 (Milling)
 *
 * NOTE: Edit this exact migration file if any columns need to be added or modified in the future.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Job Orders Table
        Schema::create('production_job_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('product_id')->nullable(); // Commodity / Product
            $table->unsignedBigInteger('export_order_id')->nullable();
            $table->string('job_order_no', 100)->unique();
            $table->date('job_order_date');
            $table->string('ref_no', 100)->nullable();
            $table->json('attention_to')->nullable();
            $table->text('order_description')->nullable(); // Order Description
            $table->text('remarks')->nullable();
            $table->json('active_phases')->nullable();
            $table->string('current_phase', 50)->default('job_order');

            // Approval & workflow tracking
            $table->enum('status', ['draft', 'posted', 'approved', 'rejected', 'in_progress', 'completed'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('maker_id')->nullable();
            $table->unsignedBigInteger('poster_id')->nullable();
            $table->unsignedBigInteger('agreeor_id')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_location_id')->references('id')->on('company_locations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('export_order_id')->references('id')->on('export_orders')->nullOnDelete();
        });

        // 2. Phase 1 - Drying
        Schema::create('production_phase1', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('company_location_id');

            // Drying mode & parameters
            $table->string('drying_mode', 50)->default('batch');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->dateTime('cycle_start_time')->nullable();
            $table->dateTime('cycle_end_time')->nullable();
            $table->string('moisture_level', 50)->default('half_dried');

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_order_id')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('company_location_id')->references('id')->on('company_locations')->cascadeOnDelete();
        });

        // 3. Phase 2 - Steaming / Parboiling
        Schema::create('production_phase2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('phase1_id')->nullable();

            // Process type & Steam type
            $table->string('process_type', 50)->default('steaming');
            $table->string('steam_type', 50)->default('single_steam');

            // Parboiling fields
            $table->decimal('parboil_soak_hours', 5, 2)->nullable();
            $table->integer('parboil_cook_minutes')->nullable();
            $table->string('parboiled_grade', 50)->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_order_id')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('company_location_id')->references('id')->on('company_locations')->cascadeOnDelete();
        });

        // 4. Phase 3 - Milling
        Schema::create('production_phase3', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('phase1_id')->nullable();
            $table->unsignedBigInteger('phase2_id')->nullable();

            $table->string('milling_type', 50)->default('direct_milling');
            $table->unsignedBigInteger('production_voucher_id')->nullable();
            $table->unsignedBigInteger('machine_plan_setting_id')->nullable();
            $table->string('storage_type', 50)->default('flat_storage');

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_order_id')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('company_location_id')->references('id')->on('company_locations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_phase3');
        Schema::dropIfExists('production_phase2');
        Schema::dropIfExists('production_phase1');
        Schema::dropIfExists('production_job_orders');
    }
};
