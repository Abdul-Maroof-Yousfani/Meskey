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
        Schema::create('arrival_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Contract information
            $table->string('contract_no')->unique();
            $table->date('contract_date');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('sauda_type_id')->nullable();
            $table->enum('purchase_type', ['regular', 'gate_buying'])->default('regular');
            $table->string('ref_no')->nullable();

            // Supplier information
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->decimal('supplier_commission', 10, 2)->nullable();
            $table->string('purchaser_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('cnic_no')->nullable();

            // Broker information
            $table->string('broker_one_name')->nullable();
            $table->unsignedBigInteger('broker_one_id')->nullable();
            $table->decimal('broker_one_commission', 10, 2)->nullable();
            $table->string('broker_two_name')->nullable();
            $table->unsignedBigInteger('broker_two_id')->nullable();
            $table->decimal('broker_two_commission', 10, 2)->nullable();
            $table->string('broker_three_name')->nullable();
            $table->unsignedBigInteger('broker_three_id')->nullable();
            $table->decimal('broker_three_commission', 10, 2)->nullable();

            // Product information
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('qc_product')->nullable();
            $table->unsignedBigInteger('truck_size_range_id')->nullable();
            $table->enum('line_type', ['bari', 'choti'])->nullable();
            $table->decimal('bag_weight', 10, 2)->nullable();
            $table->decimal('bag_rate', 10, 2)->nullable();

            // Delivery information
            $table->date('delivery_date')->nullable();
            $table->integer('credit_days')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('payment_term')->nullable();

            // Rate information
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('rate_per_mound', 10, 2);
            $table->decimal('rate_per_100kg', 10, 2);

            // Quantity calculation
            $table->enum('calculation_type', ['trucks', 'quantity'])->nullable();
            $table->integer('no_of_trucks')->nullable();
            $table->decimal('total_quantity', 12, 2)->nullable();
            $table->decimal('min_quantity', 12, 2)->nullable();
            $table->decimal('max_quantity', 12, 2)->nullable();
            $table->integer('min_bags')->nullable();
            $table->integer('max_bags')->nullable();

            $table->decimal('moisture', 5, 2)->nullable();
            $table->decimal('chalky', 5, 2)->nullable();
            $table->decimal('mixing', 5, 2)->nullable();
            $table->decimal('red_rice', 5, 2)->nullable();
            $table->text('other_params')->nullable();

            // Other fields
            $table->boolean('is_replacement')->default(false);
            $table->text('weighbridge_from')->nullable();
            $table->string('freight_status')->nullable();

            $table->tinyInteger('decision_making')->default(0);
            $table->dateTime('decision_making_time')->nullable();
            $table->decimal('lumpsum_deduction', 10, 2)->default(0);
            $table->decimal('lumpsum_deduction_kgs', 10, 2)->default(0);
            $table->tinyInteger('is_lumpsum_deduction')->default(0)->comment('Switch state for lumpsum deduction');

            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('account_of')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            // Status and timestamps
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('company_location_id')->references('id')->on('company_locations');
            $table->foreign('sauda_type_id')->references('id')->on('sauda_types');
            $table->foreign('account_of')->references('id')->on('users');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('broker_one_id')->references('id')->on('brokers');
            $table->foreign('broker_two_id')->references('id')->on('brokers');
            $table->foreign('broker_three_id')->references('id')->on('brokers');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('qc_product')->references('id')->on('products')->onDelete('set null');
            $table->foreign('truck_size_range_id')->references('id')->on('truck_size_ranges')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_purchase_orders');
    }
};
