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
        if (!Schema::hasTable('production_drying_job_orders')) {
            Schema::create('production_drying_job_orders', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('company_location_id')->nullable();
                $table->unsignedBigInteger('export_order_id')->nullable();
                $table->unsignedBigInteger('production_recipe_id')->nullable();
                $table->index('production_recipe_id', 'idx_pdjo_recipe_id');
                $table->string('recipe_name')->nullable();
                $table->string('job_order_no', 100)->unique();
                $table->date('job_order_date');
                $table->string('ref_no')->nullable();
                $table->json('attention_to')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('crop_year')->nullable();
                $table->unsignedBigInteger('crop_year_id')->nullable();
                $table->string('moisture_level')->nullable();
                $table->string('moisture_value')->nullable();
                $table->text('remarks')->nullable();
                $table->text('order_description')->nullable();
                $table->longText('other_specifications')->nullable();
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_location_id')->references('id')->on('company_locations')->nullOnDelete();
                $table->foreign('export_order_id')->references('id')->on('export_orders')->nullOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('production_drying_job_order_items')) {
            Schema::create('production_drying_job_order_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('production_drying_job_order_id');
                $table->unsignedBigInteger('production_attribute_id')->nullable();
                $table->index('production_attribute_id', 'idx_pdjoi_attr_id');
                $table->string('key');
                $table->string('type')->nullable();
                $table->text('value')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('production_drying_job_order_id', 'fk_pdjo_items_order_id')
                    ->references('id')
                    ->on('production_drying_job_orders')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('production_drying_job_order_specifications')) {
            Schema::create('production_drying_job_order_specifications', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('production_drying_job_order_id');
                $table->unsignedBigInteger('product_slab_type_id')->nullable();
                $table->index('product_slab_type_id', 'idx_pdjos_slab_type_id');
                $table->string('spec_name');
                $table->string('spec_value');
                $table->enum('value_type', ['min', 'max'])->default('min');
                $table->string('uom')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('production_drying_job_order_id', 'fk_pdjo_specs_order_id')
                    ->references('id')
                    ->on('production_drying_job_orders')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_drying_job_order_specifications');
        Schema::dropIfExists('production_drying_job_order_items');
        Schema::dropIfExists('production_drying_job_orders');
    }
};
