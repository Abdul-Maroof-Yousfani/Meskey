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
        Schema::create('commercial_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('export_order_id')
                ->nullable()
                ->constrained('export_orders')
                ->nullOnDelete();

            // Commercial Invoice Info
            $table->string('commercial_invoice_no')->nullable();
            $table->date('invoice_date')->nullable();

            // Proforma / Invoice
            $table->string('proforma_no')->nullable();
            $table->string('invoice_no')->nullable();

            // LC Details
            $table->string('lc_no')->nullable();
            $table->date('lc_date')->nullable();

            // Shipping Details
            $table->string('ship_name')->nullable();

            // Bill of Lading
            $table->string('bill_of_lading_no')->nullable();
            $table->date('bill_of_lading_date')->nullable();
            $table->string('master_bl')->nullable();

            // Consignee Details
            $table->text('consigned_details')->nullable();

            // E-Form (multiple → JSON best)
            $table->json('e_forms')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_invoices');
    }
};
