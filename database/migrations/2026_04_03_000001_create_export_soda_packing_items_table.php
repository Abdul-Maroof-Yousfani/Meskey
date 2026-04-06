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
        Schema::create('export_soda_packing_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('export_soda_id')
                ->constrained('export_soda_fields')
                ->cascadeOnDelete();

            $table->foreignId('bag_type_id')->nullable()->constrained('bag_types')->nullOnDelete();
            $table->foreignId('bag_packing_id')->nullable()->constrained('bag_packings')->nullOnDelete();

            $table->decimal('bag_size', 10, 2)->default(0);
            $table->decimal('metric_tons', 15, 4)->default(0);
            $table->decimal('maunds', 15, 4)->default(0);
            $table->integer('no_of_bags')->default(0);
            $table->decimal('total_kgs', 15, 4)->default(0);

            $table->decimal('rate', 15, 4)->default(0);
            $table->decimal('rate_per_maund', 15, 4)->default(0);
            $table->decimal('amount', 15, 4)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_soda_packing_items');
    }
};
