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
        Schema::create('purchase_order_receiving_data', function (Blueprint $table) {
           $table->id();
            $table->unsignedBigInteger('purchase_order_receiving_id');
            $table->unsignedBigInteger('purchase_order_data_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('qty', 15, 2);
            $table->decimal('rate', 15, 2)->unsigned()->default(0);
            $table->decimal('total', 15, 2)->unsigned()->default(0);
            $table->text('remarks')->nullable();
            $table->enum('receiving_order_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('por_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->string('am_approval_status')->default('pending');
            $table->Integer('am_change_made')->default(1);
            $table->timestamps();

            $table->foreign('purchase_order_receiving_id', 'po_receiving_id_fk')->references('id')->on('purchase_order_receivings')->onDelete('cascade');
            $table->foreign('purchase_order_data_id', 'po_data_id_fk')->references('id')->on('purchase_order_data')->onDelete('cascade');
            $table->foreign('category_id', 'category_id_fk')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('item_id', 'item_id_fk')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('supplier_id', 'supplier_id_fk')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receiving_data');
    }
};
