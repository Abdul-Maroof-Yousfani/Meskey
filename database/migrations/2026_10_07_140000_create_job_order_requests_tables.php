<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Main Job Order Requests Table
        if (!Schema::hasTable('job_order_requests')) {
            Schema::create('job_order_requests', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('request_no', 100)->unique();
                $table->date('request_date');
                $table->unsignedBigInteger('company_location_id')->nullable();
                $table->unsignedBigInteger('export_order_id')->nullable();
                $table->string('ref_no')->nullable();
                $table->json('attention_to')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('crop_year_id');
                $table->longText('other_specifications')->nullable();
                $table->text('remarks')->nullable();
                $table->text('order_description')->nullable();
                $table->json('inspection_company_id')->nullable();
                $table->date('loading_date')->nullable();
                $table->json('arrival_locations')->nullable();
                $table->text('packing_description')->nullable();
                $table->enum('status', ['pending', 'partial', 'converted', 'cancelled'])->default('pending');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('company_location_id')->references('id')->on('company_locations')->nullOnDelete();
                $table->foreign('export_order_id')->references('id')->on('export_orders')->nullOnDelete();
                $table->foreign('product_id')->references('id')->on('products');
                $table->foreign('crop_year_id')->references('id')->on('crop_years');
            });
        }

        // 2. Specifications Table
        if (!Schema::hasTable('job_order_request_specifications')) {
            Schema::create('job_order_request_specifications', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('job_order_request_id');
                $table->unsignedBigInteger('product_slab_type_id');
                $table->string('spec_name');
                $table->string('spec_value');
                $table->enum('value_type', ['min', 'max'])->default('min');
                $table->string('uom')->nullable();
                $table->timestamps();

                $table->foreign('job_order_request_id', 'fk_jor_specs_request_id')
                    ->references('id')->on('job_order_requests')->onDelete('cascade');
                $table->foreign('product_slab_type_id', 'fk_jor_specs_slab_type_id')
                    ->references('id')->on('product_slab_types')->onDelete('cascade');
            });
        }

        // 3. Packing Items Table
        if (!Schema::hasTable('job_order_request_packing_items')) {
            Schema::create('job_order_request_packing_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('job_order_request_id');
                $table->unsignedBigInteger('company_location_id')->nullable();
                $table->unsignedBigInteger('bag_product_id');
                $table->unsignedBigInteger('bag_condition_id');
                $table->decimal('bag_size', 8, 2);
                $table->integer('no_of_bags');
                $table->integer('extra_bags')->default(0);
                $table->decimal('extra_bags_percentage', 8, 2)->default(0);
                $table->integer('empty_bags')->default(0);
                $table->integer('total_bags')->default(0);
                $table->decimal('total_kgs', 12, 2)->default(0);
                $table->decimal('metric_tons', 8, 2)->default(0);
                $table->decimal('stuffing_in_container', 8, 2)->default(0);
                $table->integer('no_of_containers')->default(0);
                $table->unsignedBigInteger('brand_id');
                $table->unsignedBigInteger('bag_color_id');
                $table->unsignedBigInteger('thread_color_id')->nullable();
                $table->unsignedBigInteger('stitching_id')->nullable();
                $table->decimal('min_weight_empty_bags', 8, 2)->default(0);
                $table->date('delivery_date')->nullable();
                $table->json('fumigation_company_id')->nullable();
                $table->timestamps();

                $table->foreign('job_order_request_id', 'fk_jor_pi_request_id')
                    ->references('id')->on('job_order_requests')->onDelete('cascade');
                $table->foreign('company_location_id', 'fk_jor_pi_location_id')
                    ->references('id')->on('company_locations')->nullOnDelete();
                $table->foreign('bag_product_id', 'fk_jor_pi_bag_product_id')
                    ->references('id')->on('products');
                $table->foreign('bag_condition_id', 'fk_jor_pi_bag_condition_id')
                    ->references('id')->on('bag_conditions');
                $table->foreign('brand_id', 'fk_jor_pi_brand_id')
                    ->references('id')->on('brands');
                $table->foreign('bag_color_id', 'fk_jor_pi_bag_color_id')
                    ->references('id')->on('colors');
                $table->foreign('thread_color_id', 'fk_jor_pi_thread_color_id')
                    ->references('id')->on('colors')->nullOnDelete();
                $table->foreign('stitching_id', 'fk_jor_pi_stitching_id')
                    ->references('id')->on('stitchings')->nullOnDelete();
            });
        }

        // 4. Packing Sub-Items Table (Master Packing)
        if (!Schema::hasTable('job_order_request_packing_sub_items')) {
            Schema::create('job_order_request_packing_sub_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('job_order_request_packing_item_id');
                $table->unsignedBigInteger('bag_type_id')->nullable();
                $table->unsignedBigInteger('bag_product_id')->nullable();
                $table->unsignedBigInteger('bag_size_id')->nullable();
                $table->integer('no_of_primary_bags')->nullable()->default(0);
                $table->decimal('packing_size', 8, 2)->nullable();
                $table->integer('no_of_bags')->default(0);
                $table->integer('empty_bags')->default(0);
                $table->integer('extra_bags')->default(0);
                $table->decimal('extra_bags_percentage', 8, 2)->default(0);
                $table->decimal('empty_bag_weight', 8, 2)->nullable();
                $table->integer('total_bags')->default(0);
                $table->decimal('total_kgs', 12, 2)->default(0);
                $table->unsignedBigInteger('stitching_id')->nullable();
                $table->unsignedBigInteger('bag_color_id')->nullable();
                $table->unsignedBigInteger('brand_id')->nullable();
                $table->unsignedBigInteger('thread_color_id')->nullable();
                $table->string('attachment')->nullable();
                $table->timestamps();

                $table->foreign('job_order_request_packing_item_id', 'fk_jor_psi_item_id')
                    ->references('id')->on('job_order_request_packing_items')->onDelete('cascade');
                $table->foreign('bag_product_id', 'fk_jor_psi_bag_product_id')
                    ->references('id')->on('products')->nullOnDelete();
                $table->foreign('bag_size_id', 'fk_jor_psi_bag_size_id')
                    ->references('id')->on('sizes')->nullOnDelete();
                $table->foreign('stitching_id', 'fk_jor_psi_stitching_id')
                    ->references('id')->on('stitchings')->nullOnDelete();
                $table->foreign('bag_color_id', 'fk_jor_psi_bag_color_id')
                    ->references('id')->on('colors')->nullOnDelete();
                $table->foreign('brand_id', 'fk_jor_psi_brand_id')
                    ->references('id')->on('brands')->nullOnDelete();
                $table->foreign('thread_color_id', 'fk_jor_psi_thread_color_id')
                    ->references('id')->on('colors')->nullOnDelete();
            });
        }

        // 5. Container Protection Items Table
        if (!Schema::hasTable('job_order_request_container_protection_items')) {
            Schema::create('job_order_request_container_protection_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('job_order_request_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('quantity_per_container', 10, 2)->default(0);
                $table->timestamps();

                $table->foreign('job_order_request_id', 'fk_jor_cp_request_id')
                    ->references('id')->on('job_order_requests')->onDelete('cascade');
                $table->foreign('product_id', 'fk_jor_cp_product_id')
                    ->references('id')->on('products')->onDelete('cascade');
                $table->unique(['job_order_request_id', 'product_id'], 'jor_cp_items_unique');
            });
        }

        Schema::table('job_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('job_orders', 'company_location_id')) {
                $table->unsignedBigInteger('company_location_id')->nullable()->after('company_id');
                $table->foreign('company_location_id')->references('id')->on('company_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('job_orders', 'job_order_request_id')) {
                $table->unsignedBigInteger('job_order_request_id')->nullable()->after('export_order_id');
                $table->foreign('job_order_request_id')->references('id')->on('job_order_requests')->nullOnDelete();
            }
        });

        // Backfill company_location_id from packing items if available
        if (Schema::hasTable('job_orders') && Schema::hasColumn('job_orders', 'company_location_id') && Schema::hasTable('job_order_packing_items')) {
            $jobOrders = DB::table('job_orders')->select('id')->get();
            foreach ($jobOrders as $jo) {
                $packingItemLocation = DB::table('job_order_packing_items')
                    ->where('job_order_id', $jo->id)
                    ->whereNotNull('company_location_id')
                    ->value('company_location_id');

                if ($packingItemLocation) {
                    DB::table('job_orders')->where('id', $jo->id)->update([
                        'company_location_id' => $packingItemLocation
                    ]);
                }
            }
        }

        if (Schema::hasTable('stocks')) {
            Schema::table('stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('stocks', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                }
            });

            if (Schema::hasColumn('stocks', 'company_id')) {
                DB::statement("UPDATE stocks SET company_id = 1 WHERE company_id IS NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_orders')) {
            Schema::table('job_orders', function (Blueprint $table) {
                if (Schema::hasColumn('job_orders', 'job_order_request_id')) {
                    $table->dropForeign(['job_order_request_id']);
                    $table->dropColumn('job_order_request_id');
                }
                if (Schema::hasColumn('job_orders', 'company_location_id')) {
                    $table->dropForeign(['company_location_id']);
                    $table->dropColumn('company_location_id');
                }
            });
        }

        if (Schema::hasTable('stocks')) {
            Schema::table('stocks', function (Blueprint $table) {
                if (Schema::hasColumn('stocks', 'company_id')) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                }
            });
        }

        Schema::dropIfExists('job_order_request_container_protection_items');
        Schema::dropIfExists('job_order_request_packing_sub_items');
        Schema::dropIfExists('job_order_request_packing_items');
        Schema::dropIfExists('job_order_request_specifications');
        Schema::dropIfExists('job_order_requests');
    }
};
