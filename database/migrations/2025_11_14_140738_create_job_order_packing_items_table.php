<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_order_packing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_location_id')->nullable()->constrained('company_locations');
            $table->foreignId('bag_type_id')->constrained('bag_types');
            $table->foreignId('bag_condition_id')->constrained('bag_conditions');
            $table->decimal('bag_size', 8, 2);
            $table->integer('no_of_bags');
            $table->integer('extra_bags')->default(0);
            $table->integer('empty_bags')->default(0);
            $table->integer('total_bags')->default(0);
            $table->decimal('total_kgs', 12, 2)->default(0);
            $table->decimal('metric_tons', 8, 2)->default(0);
            $table->decimal('stuffing_in_container', 8, 2)->default(0);
            $table->integer('no_of_containers')->default(0);
            $table->foreignId('brand_id')->constrained('brands');
            $table->foreignId('bag_color_id')->constrained('colors');
            $table->decimal('min_weight_empty_bags', 8, 2)->default(0);
            $table->date('delivery_date')->nullable();
            $table->json('fumigation_company_id')->nullable(); // JSON for multiple fumigation companies
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_packing_items');
    }
};
