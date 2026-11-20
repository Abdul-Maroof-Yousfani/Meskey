<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * All production changes of 18-20 Nov 2026 in one migration (run on live with one --path).
 * Every step checks first, so running it where some of it already exists is safe.
 *
 *  1. Bag Request items: arrival_location_id (filled from the header), plant_id
 *  2. Machine Analysis items: analysis_type (input/output tab), product_id (filled from the analysis)
 *  3. Input Analysis "Other Products" table
 *  4. Plants: machine analysis interval + next due time
 *  5. Job orders against a Machine Analysis (pivot)
 *  6. company_user_role: plants assigned to a user
 *  7. Output QC Alerts table (date-wise alert_no)
 *  8. Analysis requests: output_alert_id (re-check request of an alert)
 *  9. Plants: shift_start_time / shift_end_time; Production Machines: is_required
 * 10. Machine Plan Settings: job_order_id (one plan per job order)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Bag Request items
        if (!Schema::hasColumn('bag_request_items', 'arrival_location_id')) {
            Schema::table('bag_request_items', function (Blueprint $table) {
                $table->unsignedBigInteger('arrival_location_id')->nullable()->after('job_order_id')->index();
            });
            DB::statement('
                UPDATE bag_request_items bri
                JOIN bag_requests br ON br.id = bri.bag_request_id
                SET bri.arrival_location_id = br.arrival_location_id
                WHERE bri.arrival_location_id IS NULL AND br.arrival_location_id IS NOT NULL
            ');
        }
        if (!Schema::hasColumn('bag_request_items', 'plant_id')) {
            Schema::table('bag_request_items', function (Blueprint $table) {
                $table->unsignedBigInteger('plant_id')->nullable()->after('arrival_location_id')->index();
            });
        }

        // 2. Machine Analysis items
        if (!Schema::hasColumn('production_machine_analysis_items', 'analysis_type')) {
            Schema::table('production_machine_analysis_items', function (Blueprint $table) {
                $table->string('analysis_type', 10)->default('input')->after('machine_analysis_id');
            });
        }
        if (!Schema::hasColumn('production_machine_analysis_items', 'product_id')) {
            Schema::table('production_machine_analysis_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->after('analysis_type');
            });
            DB::statement('
                UPDATE production_machine_analysis_items i
                JOIN production_machine_analysis a ON a.id = i.machine_analysis_id
                SET i.product_id = a.product_id
                WHERE i.product_id IS NULL
            ');
        }

        // 3. Input Analysis "Other Products"
        if (!Schema::hasTable('production_analysis_other_products')) {
            Schema::create('production_analysis_other_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_analysis_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('percentage', 8, 2)->default(0);
                $table->timestamps();

                $table->foreign('production_analysis_id', 'pa_other_products_analysis_fk')
                    ->references('id')->on('production_analysis')->onDelete('cascade');
                $table->index('product_id');
            });
        }

        // 4. Plants: machine analysis interval
        Schema::table('plants', function (Blueprint $table) {
            if (!Schema::hasColumn('plants', 'machine_min_interval')) {
                $table->unsignedInteger('machine_min_interval')->nullable()->after('output_max_interval');
            }
            if (!Schema::hasColumn('plants', 'machine_max_interval')) {
                $table->unsignedInteger('machine_max_interval')->nullable()->after('machine_min_interval');
            }
            if (!Schema::hasColumn('plants', 'next_machine_analysis_at')) {
                $table->timestamp('next_machine_analysis_at')->nullable()->after('next_output_analysis_at');
            }
        });

        // 5. Job orders against a Machine Analysis (created only when missing, existing rows are kept)
        if (!Schema::hasTable('job_orders_against_production_machine_analysis')) {
            Schema::create('job_orders_against_production_machine_analysis', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_order_id');
                $table->unsignedBigInteger('machine_analysis_id');
                $table->timestamps();

                $table->foreign('job_order_id', 'fk_jo_pma_jo')->references('id')->on('job_orders')->cascadeOnDelete();
                $table->foreign('machine_analysis_id', 'fk_jo_pma_ma')->references('id')->on('production_machine_analysis')->cascadeOnDelete();
            });
        }

        // 6. Plants assigned to a user (JSON list of plant ids), like arrival_locations
        if (!Schema::hasColumn('company_user_role', 'plants')) {
            Schema::table('company_user_role', function (Blueprint $table) {
                $table->json('plants')->nullable()->after('arrival_locations');
            });
        }

        // 7. Output QC Alerts
        if (!Schema::hasTable('production_output_alerts')) {
            Schema::create('production_output_alerts', function (Blueprint $table) {
                $table->engine = 'InnoDB'; // saved or rolled back together with its analysis
                $table->id();
                $table->string('alert_no', 50)->nullable()->unique();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('production_analysis_id')->index();
                $table->unsignedBigInteger('analysis_request_id')->nullable()->index();
                $table->unsignedBigInteger('parent_alert_id')->nullable()->index();
                $table->unsignedInteger('attempt')->default(1);
                $table->unsignedBigInteger('company_location_id')->nullable();
                $table->unsignedBigInteger('arrival_location_id')->nullable();
                $table->unsignedBigInteger('plant_id')->nullable()->index();
                $table->unsignedBigInteger('job_order_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->json('checklist')->nullable();
                $table->unsignedInteger('difference_count')->default(0);
                $table->string('status', 20)->default('pending');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('remarked_by')->nullable();
                $table->timestamp('remarked_at')->nullable();
                $table->unsignedBigInteger('new_analysis_request_id')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('production_output_alerts', 'alert_no')) {
            Schema::table('production_output_alerts', function (Blueprint $table) {
                $table->string('alert_no', 50)->nullable()->unique()->after('id');
            });
        }

        // 8. Re-check request of an alert
        if (!Schema::hasColumn('production_analysis_requests', 'output_alert_id')) {
            Schema::table('production_analysis_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('output_alert_id')->nullable()->index()->after('machine_plan_setting_id');
            });
        }

        // 9. Shift Time on the plant; Is Required on the machine
        if (!Schema::hasColumn('plants', 'shift_start_time')) {
            Schema::table('plants', function (Blueprint $table) {
                $table->time('shift_start_time')->nullable()->default('09:00:00');
                $table->time('shift_end_time')->nullable()->default('21:00:00');
            });
        }
        if (Schema::hasColumn('production_machines', 'start_time')) {
            // Where machines still have a working time, the plant takes its first machine's time
            foreach (DB::table('plants')->pluck('id') as $plantId) {
                $machine = DB::table('production_machines')->where('plant_id', $plantId)
                    ->whereNotNull('start_time')->whereNotNull('end_time')->orderBy('id')->first(['start_time', 'end_time']);
                if ($machine) {
                    DB::table('plants')->where('id', $plantId)->update([
                        'shift_start_time' => $machine->start_time,
                        'shift_end_time' => $machine->end_time,
                    ]);
                }
            }
            Schema::table('production_machines', function (Blueprint $table) {
                $table->dropColumn(['start_time', 'end_time']);
            });
        }
        if (!Schema::hasColumn('production_machines', 'is_required')) {
            Schema::table('production_machines', function (Blueprint $table) {
                $table->boolean('is_required')->default(false)->after('description');
            });
        }

        // 10. One Machine Plan per job order; older plans take the first job order of their machine rows
        if (!Schema::hasColumn('machine_plan_settings', 'job_order_id')) {
            Schema::table('machine_plan_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('job_order_id')->nullable()->index()->after('plant_id');
            });

            foreach (DB::table('machine_plan_settings')->pluck('id') as $planId) {
                $jobOrderId = DB::table('production_voucher_machine_times')
                    ->where('machine_plan_setting_id', $planId)
                    ->whereNotNull('job_order_ids')
                    ->orderBy('id')
                    ->pluck('job_order_ids')
                    ->map(fn ($json) => (array) json_decode($json, true))
                    ->flatten()
                    ->filter()
                    ->first();

                if ($jobOrderId) {
                    DB::table('machine_plan_settings')->where('id', $planId)->update(['job_order_id' => (int) $jobOrderId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('machine_plan_settings', 'job_order_id')) {
            Schema::table('machine_plan_settings', fn (Blueprint $t) => $t->dropColumn('job_order_id'));
        }
        if (Schema::hasColumn('production_machines', 'is_required')) {
            Schema::table('production_machines', fn (Blueprint $t) => $t->dropColumn('is_required'));
        }
        if (Schema::hasColumn('plants', 'shift_start_time')) {
            Schema::table('plants', fn (Blueprint $t) => $t->dropColumn(['shift_start_time', 'shift_end_time']));
        }
        if (Schema::hasColumn('production_analysis_requests', 'output_alert_id')) {
            Schema::table('production_analysis_requests', fn (Blueprint $t) => $t->dropColumn('output_alert_id'));
        }
        Schema::dropIfExists('production_output_alerts');
        if (Schema::hasColumn('company_user_role', 'plants')) {
            Schema::table('company_user_role', fn (Blueprint $t) => $t->dropColumn('plants'));
        }
        Schema::dropIfExists('job_orders_against_production_machine_analysis');
        Schema::table('plants', function (Blueprint $t) {
            foreach (['machine_min_interval', 'machine_max_interval', 'next_machine_analysis_at'] as $col) {
                if (Schema::hasColumn('plants', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('production_analysis_other_products');
        if (Schema::hasColumn('production_machine_analysis_items', 'product_id')) {
            Schema::table('production_machine_analysis_items', fn (Blueprint $t) => $t->dropColumn('product_id'));
        }
        if (Schema::hasColumn('production_machine_analysis_items', 'analysis_type')) {
            Schema::table('production_machine_analysis_items', fn (Blueprint $t) => $t->dropColumn('analysis_type'));
        }
        if (Schema::hasColumn('bag_request_items', 'plant_id')) {
            Schema::table('bag_request_items', fn (Blueprint $t) => $t->dropColumn('plant_id'));
        }
        if (Schema::hasColumn('bag_request_items', 'arrival_location_id')) {
            Schema::table('bag_request_items', fn (Blueprint $t) => $t->dropColumn('arrival_location_id'));
        }
    }
};
