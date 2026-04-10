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
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->foreignId('export_order_id')->nullable()->after('sale_order_id')->constrained('export_orders')->onDelete('cascade');
            $table->dropForeign(['sale_order_id']);
            $table->dropForeign(['delivery_order_id']);
            $table->unsignedBigInteger('sale_order_id')->nullable()->change();
            $table->unsignedBigInteger('delivery_order_id')->nullable()->change();
            $table->foreign('sale_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('delivery_order_id')->references('id')->on('delivery_order')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->dropForeign(['export_order_id']);
            $table->dropColumn('export_order_id');
            $table->dropForeign(['sale_order_id']);
            $table->dropForeign(['delivery_order_id']);
            $table->unsignedBigInteger('sale_order_id')->nullable(false)->change();
            $table->unsignedBigInteger('delivery_order_id')->nullable(false)->change();
            $table->foreign('sale_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('delivery_order_id')->references('id')->on('delivery_order')->onDelete('cascade');
        });
    }
};
