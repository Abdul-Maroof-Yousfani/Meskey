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
        Schema::create('good_receive_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('stock_id');

            $table->string('grn_number')->unique();
            $table->string('reference_number')->nullable();

            // Related entities
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();

            // Polymorphic relationship (from original grn_numbers table)
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_type')->nullable();

            // Stock information (from original stocks table)
            $table->enum('voucher_type', ['grn', 'gdn', 'sale_return', 'purchase_return'])->default('grn');
            $table->string('voucher_no');
            $table->decimal('qty', 12, 2);
            $table->enum('type', ['stock-in', 'stock-out'])->default('stock-in');
            $table->text('narration')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('avg_price_per_kg', 12, 2)->nullable();

            // Status and dates
            $table->enum('status', ['draft', 'received', 'verified', 'cancelled'])->default('draft');
            $table->dateTime('received_at')->nullable();
            $table->dateTime('verified_at')->nullable();

            // Personnel information
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();

            // Additional information
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Product batch information
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            // Quality information
            $table->enum('quality_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('quality_notes')->nullable();
            $table->decimal('accepted_quantity', 12, 2)->nullable();
            $table->decimal('rejected_quantity', 12, 2)->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('grn_id')->references('id')->on('grn_numbers')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['model_id', 'model_type']);
            $table->index('voucher_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('good_receive_notes');
    }
};
