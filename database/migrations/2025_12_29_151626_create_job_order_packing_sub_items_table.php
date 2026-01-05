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
            $table->foreignId('bag_type_id')->constrained('bag_types');
            $table->decimal('bag_size', 8, 2);
            $table->integer('no_of_bags');
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
