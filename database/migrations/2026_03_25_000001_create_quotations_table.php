<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // Export Soda
            $table->foreignId('export_soda_id')->nullable()->constrained('export_soda_fields')->nullOnDelete();

            // Product
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->foreignId('buyer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->json('company_location_ids')->nullable();
            $table->json('arrival_location_ids')->nullable();
            $table->json('arrival_sub_location_ids')->nullable();

            // Export Details
            $table->foreignId('incoterm_id')->nullable()->constrained('inco_terms')->nullOnDelete();
            $table->string('packing_type')->nullable();
            $table->foreignId('mode_of_term_id')->nullable()->constrained('mode_of_terms')->nullOnDelete();
            $table->foreignId('mode_of_transport_id')->nullable()->constrained('mode_of_transports')->nullOnDelete();
            $table->foreignId('origin_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('port_of_discharge_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('port_of_loading_id')->nullable()->constrained('ports')->nullOnDelete();

            // Payment
            $table->decimal('advance_payment', 10, 2)->nullable();
            $table->integer('payment_days')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('currency_rate', 10, 4)->nullable();

            // Quantity (Global)
            $table->decimal('stuffing_in_container', 15, 4)->nullable()->default(0);
            $table->decimal('stuffing_maunds', 15, 4)->nullable()->default(0);
            $table->integer('no_of_containers')->nullable()->default(0);

            // Price (Global)
            $table->decimal('total_amount', 15, 4)->nullable()->default(0);

            // Meta
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
