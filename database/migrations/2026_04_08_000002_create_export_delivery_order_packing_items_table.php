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
        // Drop manually created tables if they exist to avoid conflicts
        Schema::dropIfExists('delivery_order_sub_packing_items');
        Schema::dropIfExists('delivery_order_packing_items');

        Schema::create('export_delivery_order_packing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_order')->onDelete('cascade');
            $table->foreignId('company_location_id')->nullable()->constrained('company_locations')->nullOnDelete();
            $table->foreignId('bag_type_id')->nullable()->constrained('bag_types')->nullOnDelete();
            $table->foreignId('bag_condition_id')->nullable()->constrained('bag_conditions')->nullOnDelete();
            $table->decimal('bag_size', 8, 2)->nullable();
            $table->integer('no_of_bags')->nullable()->default(0);
            $table->integer('extra_bags')->nullable()->default(0);
            $table->integer('empty_bags')->nullable()->default(0);
            $table->integer('total_bags')->nullable()->default(0);
            $table->decimal('total_kgs', 12, 2)->nullable()->default(0);
            $table->decimal('metric_tons', 12, 4)->nullable()->default(0);
            $table->decimal('stuffing_in_container', 8, 2)->nullable()->default(0);
            $table->integer('no_of_containers')->nullable()->default(0);
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('bag_color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('thread_color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('stitching_id')->nullable()->constrained('stitchings')->nullOnDelete();
            $table->decimal('min_weight_empty_bags', 8, 2)->nullable()->default(0);
            $table->decimal('extra_bags_percentage', 8, 2)->nullable()->default(0);
            $table->json('fumigation_company_id')->nullable();
            $table->timestamps();
        });

        Schema::create('export_delivery_order_packing_sub_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_delivery_order_packing_item_id');
            $table->foreign('export_delivery_order_packing_item_id', 'fk_ex_do_pk_sub')->references('id')->on('export_delivery_order_packing_items')->onDelete('cascade');
            $table->foreignId('bag_type_id')->nullable()->constrained('bag_types')->nullOnDelete();
            $table->foreignId('bag_size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->integer('no_of_primary_bags')->nullable()->default(0);
            $table->integer('no_of_bags')->nullable()->default(0);
            $table->integer('empty_bags')->nullable()->default(0);
            $table->integer('extra_bags')->nullable()->default(0);
            $table->decimal('empty_bag_weight', 8, 2)->nullable();
            $table->integer('total_bags')->nullable()->default(0);
            $table->decimal('total_kgs', 12, 2)->nullable()->default(0);
            $table->foreignId('stitching_id')->nullable()->constrained('stitchings')->nullOnDelete();
            $table->foreignId('bag_color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('thread_color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_delivery_order_packing_sub_items');
        Schema::dropIfExists('export_delivery_order_packing_items');
    }
};
