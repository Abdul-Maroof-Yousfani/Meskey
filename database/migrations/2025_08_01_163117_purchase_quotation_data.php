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
         Schema::create('purchase_quotation_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_quotation_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('qty', 15, 2);
            $table->decimal('rate', 15, 2)->unsigned()->default(0);
            $table->decimal('total', 15, 2)->unsigned()->default(0);
            $table->text('remarks')->nullable();
            $table->enum('quotation_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('po_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->timestamps();

            $table->foreign('purchase_quotation_id')
            ->references('id')->on('purchase_quotations')
            ->onDelete('cascade'); // Cascade on hard delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('purchase_quotation_data');
    }
};
