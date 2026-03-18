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
        // Pivot table for Loading Program and Sale Orders
        Schema::create('loading_program_sale_order', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('loading_program_id');
            $table->unsignedBigInteger('sale_order_id');

            $table->foreign('loading_program_id', 'lp_so_lp_fk')
                ->references('id')
                ->on('loading_programs')
                ->onDelete('cascade');

            $table->foreign('sale_order_id', 'lp_so_so_fk')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('cascade');

            $table->timestamps();
        });

        // Pivot table for Loading Program and Delivery Orders
        Schema::create('loading_program_delivery_order', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('loading_program_id');
            $table->unsignedBigInteger('delivery_order_id');

            $table->foreign('loading_program_id', 'lp_do_lp_fk')
                ->references('id')
                ->on('loading_programs')
                ->onDelete('cascade');

            $table->foreign('delivery_order_id', 'lp_do_do_fk')
                ->references('id')
                ->on('delivery_order')
                ->onDelete('cascade');

            $table->timestamps();
        });

        // Pivot table for Loading Program Items and Sale Orders
        Schema::create('loading_program_item_sale_order', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('loading_program_item_id');
            $table->unsignedBigInteger('sale_order_id');

            $table->foreign('loading_program_item_id', 'lpi_so_lpi_fk')
                ->references('id')
                ->on('loading_program_items')
                ->onDelete('cascade');

            $table->foreign('sale_order_id', 'lpi_so_so_fk')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('cascade');

            $table->timestamps();
        });

        // Pivot table for Loading Program Items and Delivery Orders
        Schema::create('loading_program_item_delivery_order', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('loading_program_item_id');
            $table->unsignedBigInteger('delivery_order_id');

            $table->foreign('loading_program_item_id', 'lpi_do_lpi_fk')
                ->references('id')
                ->on('loading_program_items')
                ->onDelete('cascade');

            $table->foreign('delivery_order_id', 'lpi_do_do_fk')
                ->references('id')
                ->on('delivery_order')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_program_item_delivery_order');
        Schema::dropIfExists('loading_program_item_sale_order');
        Schema::dropIfExists('loading_program_delivery_order');
        Schema::dropIfExists('loading_program_sale_order');
    }
};