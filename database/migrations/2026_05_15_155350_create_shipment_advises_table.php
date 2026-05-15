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
        Schema::create('shipment_advises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_list_id')->nullable()->constrained('packing_lists')->nullOnDelete();
            $table->foreignId('commercial_invoice_id')->nullable()->constrained('commercial_invoices')->nullOnDelete();
            $table->foreignId('bill_of_lading_id')->nullable()->constrained('bill_of_ladings')->nullOnDelete();
            $table->json('snapshot_data')->nullable();
            $table->json('goods_summary')->nullable();
            $table->text('remarks')->nullable();
            $table->string('am_approval_status', 20)->default('pending')->comment('pending, approved, rejected, revision');
            $table->boolean('am_change_made')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_advises');
    }
};
