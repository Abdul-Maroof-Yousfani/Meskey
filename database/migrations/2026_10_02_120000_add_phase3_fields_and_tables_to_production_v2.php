<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Phase 3 (Old Production Flow) follow-up fields and tables in Production V2.
 * 
 * Includes:
 * 1. Additional fields on production_job_orders (crop_year_id, other_specifications, inspection_company_id, arrival_locations, loading_date, packing_description)
 * 2. production_job_order_packing_items
 * 3. production_job_order_packing_sub_items
 * 4. production_job_order_specifications
 * 5. production_job_order_container_protection_items
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Phase 3 follow-up fields to production_job_orders
        Schema::table('production_job_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('crop_year_id')->nullable()->after('current_phase');
            $table->foreign('crop_year_id')->references('id')->on('crop_years')->nullOnDelete();
            $table->longText('other_specifications')->nullable()->after('crop_year_id');
            $table->json('inspection_company_id')->nullable()->after('other_specifications');
            $table->json('arrival_locations')->nullable()->after('inspection_company_id');
            $table->date('loading_date')->nullable()->after('arrival_locations');
            $table->text('packing_description')->nullable()->after('loading_date');
            $table->unsignedBigInteger('current_stage')->nullable()->default(1)->after('active_phases');
        });

        // 2. Production Job Order Packing Items
        Schema::create('production_job_order_packing_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('company_location_id')->nullable();
            $table->unsignedBigInteger('bag_product_id');
            $table->unsignedBigInteger('bag_condition_id');
            $table->decimal('bag_size', 8, 2);
            $table->integer('no_of_bags');
            $table->integer('extra_bags')->default(0);
            $table->decimal('extra_bags_percentage', 8, 2)->nullable();
            $table->integer('empty_bags')->default(0);
            $table->integer('total_bags')->default(0);
            $table->decimal('total_kgs', 12, 2)->default(0.00);
            $table->decimal('metric_tons', 8, 2)->default(0.00);
            $table->decimal('stuffing_in_container', 8, 2)->default(0.00);
            $table->integer('no_of_containers')->default(0);
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('bag_color_id');
            $table->integer('thread_color_id')->nullable();
            $table->integer('stitching_id')->nullable();
            $table->decimal('min_weight_empty_bags', 8, 2)->default(0.00);
            $table->date('delivery_date')->nullable();
            $table->json('fumigation_company_id')->nullable();
            $table->text('description')->nullable();
            $table->text('location_instruction')->nullable();
            $table->timestamps();

            $table->foreign('job_order_id')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('company_location_id')->references('id')->on('company_locations')->nullOnDelete();
            $table->foreign('bag_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('bag_condition_id')->references('id')->on('bag_conditions')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
            $table->foreign('bag_color_id')->references('id')->on('colors')->cascadeOnDelete();
        });

        // 3. Production Job Order Packing Sub Items (Master Packing)
        Schema::create('production_job_order_packing_sub_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_order_packing_item_id');
            $table->unsignedBigInteger('bag_product_id')->nullable();
            $table->unsignedBigInteger('bag_size_id')->nullable();
            $table->integer('no_of_primary_bags')->default(0);
            $table->decimal('packing_size', 10, 2)->nullable();
            $table->integer('no_of_bags')->default(0);
            $table->integer('empty_bags')->default(0);
            $table->integer('extra_bags')->default(0);
            $table->decimal('extra_bags_percentage', 8, 2)->nullable();
            $table->decimal('empty_bag_weight', 8, 2)->nullable();
            $table->integer('total_bags')->default(0);
            $table->decimal('total_kgs', 12, 2)->default(0.00);
            $table->unsignedBigInteger('stitching_id')->nullable();
            $table->unsignedBigInteger('bag_color_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('thread_color_id')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->timestamps();

            $table->foreign('job_order_packing_item_id', 'pjopsi_parent_fk')->references('id')->on('production_job_order_packing_items')->cascadeOnDelete();
            $table->foreign('bag_product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('bag_size_id')->references('id')->on('sizes')->nullOnDelete();
            $table->foreign('stitching_id')->references('id')->on('stitchings')->nullOnDelete();
            $table->foreign('bag_color_id')->references('id')->on('colors')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('thread_color_id')->references('id')->on('colors')->nullOnDelete();
        });

        // 4. Production Job Order Specifications
        Schema::create('production_job_order_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('product_slab_type_id');
            $table->string('spec_name', 255);
            $table->string('spec_value', 255)->nullable();
            $table->enum('value_type', ['min', 'max'])->default('min');
            $table->string('uom', 255)->nullable();
            $table->timestamps();

            $table->foreign('job_order_id')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('product_slab_type_id')->references('id')->on('product_slab_types')->cascadeOnDelete();
        });

        // 5. Production Job Order Container Protection Items
        Schema::create('production_job_order_container_protection_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_per_container', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['job_order_id', 'product_id'], 'pjocp_unique');
            $table->foreign('job_order_id', 'pjocp_jo_fk')->references('id')->on('production_job_orders')->cascadeOnDelete();
            $table->foreign('product_id', 'pjocp_prod_fk')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_job_order_container_protection_items');
        Schema::dropIfExists('production_job_order_specifications');
        Schema::dropIfExists('production_job_order_packing_sub_items');
        Schema::dropIfExists('production_job_order_packing_items');

        if (Schema::hasTable('production_job_orders')) {
            Schema::table('production_job_orders', function (Blueprint $table) {
                $table->dropColumn('current_stage');
                $table->dropForeign(['crop_year_id']);
                $table->dropColumn('crop_year_id');
                $table->dropColumn('other_specifications');
                $table->dropColumn('inspection_company_id');
                $table->dropColumn('arrival_locations');
                $table->dropColumn('loading_date');
                $table->dropColumn('packing_description');
            });
        }
    }
};
