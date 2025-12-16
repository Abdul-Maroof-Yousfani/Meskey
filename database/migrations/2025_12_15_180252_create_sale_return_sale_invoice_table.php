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
        Schema::create('sale_return_sale_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId("sale_return_id")->constrained("sales_return")->cascadeOnDelete();
            $table->foreignId("sale_invoice_id")->constrained("sales_invoices")->cascadeOnDelete();
            $table->unsignedInteger("qty");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_sale_invoice');
    }
};
