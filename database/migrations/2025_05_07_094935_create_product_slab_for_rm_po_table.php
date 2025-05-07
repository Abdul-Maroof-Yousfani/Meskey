<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_slab_for_rm_po', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arrival_purchase_order_id');
            $table->unsignedBigInteger('slab_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_slab_type_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('deduction_type')->nullable();
            $table->string('deduction_value')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('arrival_purchase_order_id')->references('id')->on('arrival_purchase_orders')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('slab_id')->references('id')->on('product_slabs')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('product_slab_type_id')->references('id')->on('product_slab_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_slab_for_rm_po');
    }
};
