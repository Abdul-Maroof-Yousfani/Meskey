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
        Schema::create('export_order_packing_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('export_order_id')
                ->constrained()
                ->cascadeOnDelete();

            // $table->foreignId('company_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('bag_type_id')->nullable()->constrained('bag_types')->nullOnDelete();
            $table->foreignId('bag_packing_id')->nullable()->constrained('bag_packings')->nullOnDelete();
            $table->foreignId('bag_condition_id')->nullable()->constrained('bag_conditions')->nullOnDelete();
            $table->foreignId('bag_color_id')->nullable()->constrained('colors')->nullOnDelete();

            $table->decimal('bag_size', 10, 2)->default(0);
            $table->decimal('metric_tons', 10, 3)->default(0);

            $table->integer('no_of_bags')->default(0);
            $table->decimal('total_kgs', 12, 2)->default(0);

            $table->decimal('stuffing_in_container', 10, 3)->default(0);
            $table->integer('no_of_containers')->default(0);

            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('amount_pkr', 14, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_order_packing_items');
    }
};
