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
        Schema::create('job_order_packing_sub_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_packing_item_id')->constrained('job_order_packing_items')->onDelete('cascade');
            $table->foreignId('bag_product_id')->nullable()->constrained('products');
            $table->foreignId('bag_size_id')->nullable()->constrained('sizes');
            $table->integer('no_of_primary_bags')->nullable()->default(0);
            $table->integer('no_of_bags')->default(0);
            $table->integer('empty_bags')->default(0);
            $table->integer('extra_bags')->default(0);
            $table->decimal('empty_bag_weight', 8, 2)->nullable();
            $table->integer('total_bags')->default(0);
            $table->decimal('total_kgs', 12, 2)->default(0);
            $table->foreignId('stitching_id')->nullable()->constrained('stitchings');
            $table->foreignId('bag_color_id')->nullable()->constrained('colors');
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->foreignId('thread_color_id')->nullable()->constrained('colors');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_packing_sub_items');
    }
};
