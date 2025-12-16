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
        Schema::create('export_orders', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Voucher / Contract
            $table->string('voucher_no')->unique();
            $table->string('contract_no')->nullable();
            $table->date('voucher_date')->nullable();
            $table->string('voucher_heading')->nullable();

            // Shipment Delivery Dates
            $table->date('shipment_delivery_date_from')->nullable();
            $table->date('shipment_delivery_date_to')->nullable();

            // Specifications & Product
            $table->longText('other_specifications')->nullable();

            // Bank Details
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('correspondent_bank_id')->nullable()->constrained('banks')->nullOnDelete();

            // Export Related Fields
            $table->foreignId('incoterm_id')->nullable()->constrained('inco_terms')->nullOnDelete();
            $table->enum('packing_type', ['In Conatiner', 'In Bulk'])->nullable();
            $table->foreignId('mode_of_term_id')->nullable()->constrained('mode_of_terms')->nullOnDelete();
            $table->foreignId('mode_of_transport_id')->nullable()->constrained('mode_of_transports')->nullOnDelete();
            $table->foreignId('origin_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('port_of_discharge_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('port_of_loading_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('hs_code_id')->nullable()->constrained('hs_codes')->nullOnDelete();

            $table->string('partial_payment')->nullable();
            $table->string('transhipment')->nullable();
            $table->string('part_shipment')->nullable();
            $table->string('insurance_covered_by')->nullable();
            $table->decimal('advance_payment', 10, 2)->nullable();
            $table->integer('payment_days')->nullable();

            // Currency
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('currency_rate', 10, 4)->nullable();

            // Text Areas
            $table->text('marking_labeling')->nullable();
            $table->text('shipping_instructions')->nullable();
            $table->text('documents_to_be_provided')->nullable();
            $table->text('other_condition')->nullable();
            $table->text('force_majure')->nullable();
            $table->text('application_law')->nullable();

            // Broker
            $table->foreignId('broker_id')->nullable()->constrained('brokers')->nullOnDelete();

            // Packing
            // $table->text('packing_description')->nullable();

            // Arrival Locations
            // $table->json('arrival_locations')->nullable();
            // $table->json('inspection_company_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_orders');
    }
};
