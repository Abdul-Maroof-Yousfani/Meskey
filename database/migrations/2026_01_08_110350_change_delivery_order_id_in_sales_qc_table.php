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
        Schema::table('sales_qc', function (Blueprint $table) {
            // 1. Add the column (match type exactly)
            $table->unsignedBigInteger('delivery_order_id')->nullable();

            // 2. Add foreign key constraint
            $table->foreign('delivery_order_id')
                ->references('id')
                ->on('delivery_orders')
                ->onDelete('set null'); // optional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_qc', function (Blueprint $table) {
            //
        });
    }
};
