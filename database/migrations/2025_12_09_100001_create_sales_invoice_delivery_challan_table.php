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
        Schema::create('sales_invoice_delivery_challan', function (Blueprint $table) {
            $table->id();
            $table->foreignId("sales_invoice_id")->constrained("sales_invoices")->cascadeOnDelete();
            $table->foreignId("delivery_challan_id")->constrained("delivery_challans")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_delivery_challan');
    }
};

