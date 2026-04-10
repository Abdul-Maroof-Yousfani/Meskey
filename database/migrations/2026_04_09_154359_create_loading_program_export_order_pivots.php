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
        Schema::create('loading_program_export_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_program_id')->constrained('loading_programs', 'id', 'lp_eo_lp_id_foreign')->onDelete('cascade');
            $table->foreignId('export_order_id')->constrained('export_orders', 'id', 'lp_eo_eo_id_foreign')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('loading_program_item_export_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_program_item_id')->constrained('loading_program_items', 'id', 'lpi_eo_lpi_id_foreign')->onDelete('cascade');
            $table->foreignId('export_order_id')->constrained('export_orders', 'id', 'lpi_eo_eo_id_foreign')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_program_item_export_order');
        Schema::dropIfExists('loading_program_export_order');
    }
};
