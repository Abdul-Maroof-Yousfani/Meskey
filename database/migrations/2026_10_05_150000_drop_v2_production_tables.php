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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('production_job_order_phase_parameters');
        Schema::dropIfExists('production_job_order_container_protection_items');
        Schema::dropIfExists('production_job_order_specifications');
        Schema::dropIfExists('production_job_order_packing_sub_items');
        Schema::dropIfExists('production_job_order_packing_items');
        Schema::dropIfExists('production_phase3');
        Schema::dropIfExists('production_phase2');
        Schema::dropIfExists('production_phase1');
        Schema::dropIfExists('production_job_orders');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropped permanently as part of removing Production V2
    }
};
