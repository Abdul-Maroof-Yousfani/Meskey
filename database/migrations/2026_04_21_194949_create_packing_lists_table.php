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
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_order_id')->nullable()->constrained('export_orders')->nullOnDelete();
            $table->foreignId('commercial_invoice_id')->nullable()->constrained('commercial_invoices')->nullOnDelete()->unique();
            $table->foreignId('bill_of_lading_id')->nullable()->constrained('bill_of_ladings')->nullOnDelete();
            $table->json('snapshot_data')->nullable();
            $table->json('goods_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_lists');
    }
};
