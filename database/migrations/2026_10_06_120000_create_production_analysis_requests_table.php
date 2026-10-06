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
        // 1. Create production_analysis_requests table with InnoDB engine
        Schema::create('production_analysis_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('request_no', 100)->unique();
            $table->date('request_date');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('company_location_id')->nullable();
            $table->unsignedBigInteger('arrival_location_id')->nullable();
            $table->unsignedBigInteger('plant_id')->nullable();
            $table->unsignedBigInteger('job_order_id')->nullable();
            $table->enum('type', [
                'production-input-analysis',
                'production-output-analysis',
                'production-machine-analysis',
            ]);
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('company_location_id')->references('id')->on('company_locations')->cascadeOnDelete();
            $table->foreign('arrival_location_id')->references('id')->on('arrival_locations')->cascadeOnDelete();
            $table->foreign('plant_id')->references('id')->on('plants')->cascadeOnDelete();
            $table->foreign('job_order_id')->references('id')->on('job_orders')->nullOnDelete();
        });

        // 2. Add analysis_request_id to production_analysis if not present
        if (Schema::hasTable('production_analysis')) {
            Schema::table('production_analysis', function (Blueprint $table) {
                if (!Schema::hasColumn('production_analysis', 'analysis_request_id')) {
                    $table->unsignedBigInteger('analysis_request_id')->nullable()->after('id');
                    $table->foreign('analysis_request_id')->references('id')->on('production_analysis_requests')->nullOnDelete();
                }
            });
        }

        // 3. Add analysis_request_id to production_machine_analysis if not present
        if (Schema::hasTable('production_machine_analysis')) {
            Schema::table('production_machine_analysis', function (Blueprint $table) {
                if (!Schema::hasColumn('production_machine_analysis', 'analysis_request_id')) {
                    $table->unsignedBigInteger('analysis_request_id')->nullable()->after('id');
                    $table->foreign('analysis_request_id')->references('id')->on('production_analysis_requests')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('production_machine_analysis') && Schema::hasColumn('production_machine_analysis', 'analysis_request_id')) {
            Schema::table('production_machine_analysis', function (Blueprint $table) {
                $table->dropForeign(['analysis_request_id']);
                $table->dropColumn('analysis_request_id');
            });
        }

        if (Schema::hasTable('production_analysis') && Schema::hasColumn('production_analysis', 'analysis_request_id')) {
            Schema::table('production_analysis', function (Blueprint $table) {
                $table->dropForeign(['analysis_request_id']);
                $table->dropColumn('analysis_request_id');
            });
        }

        Schema::dropIfExists('production_analysis_requests');
    }
};
