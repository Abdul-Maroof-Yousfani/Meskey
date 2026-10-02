<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Phase 1 & Phase 2 dynamic parameters in Production V2.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create production_job_order_phase_parameters table
        if (!Schema::hasTable('production_job_order_phase_parameters')) {
            Schema::create('production_job_order_phase_parameters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('job_order_id');
                $table->unsignedBigInteger('phase_id'); // 1 = Phase 1, 2 = Phase 2
                $table->unsignedBigInteger('production_attribute_id')->nullable();
                $table->string('key');
                $table->string('type')->default('text');
                $table->text('value')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['job_order_id', 'phase_id'], 'pjopp_jo_phase_idx');
                $table->foreign('job_order_id', 'pjopp_jo_fk')->references('id')->on('production_job_orders')->cascadeOnDelete();
                $table->foreign('production_attribute_id', 'pjopp_attr_fk')->references('id')->on('prodction_attribute')->nullOnDelete();
            });
        }

        // 2. Add parameters JSON column to production_phase1 if not exists
        if (Schema::hasTable('production_phase1')) {
            Schema::table('production_phase1', function (Blueprint $table) {
                if (!Schema::hasColumn('production_phase1', 'parameters')) {
                    $table->json('parameters')->nullable()->after('status');
                }
            });
        }

        // 3. Add parameters JSON column to production_phase2 if not exists
        if (Schema::hasTable('production_phase2')) {
            Schema::table('production_phase2', function (Blueprint $table) {
                if (!Schema::hasColumn('production_phase2', 'parameters')) {
                    $table->json('parameters')->nullable()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_job_order_phase_parameters');

        if (Schema::hasTable('production_phase1')) {
            Schema::table('production_phase1', function (Blueprint $table) {
                if (Schema::hasColumn('production_phase1', 'parameters')) {
                    $table->dropColumn('parameters');
                }
            });
        }

        if (Schema::hasTable('production_phase2')) {
            Schema::table('production_phase2', function (Blueprint $table) {
                if (Schema::hasColumn('production_phase2', 'parameters')) {
                    $table->dropColumn('parameters');
                }
            });
        }
    }
};
