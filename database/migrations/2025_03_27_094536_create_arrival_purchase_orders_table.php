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

            // Basic information
            $table->string('contract_no')->unique();
            $table->date('contract_date');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('sauda_type_id');

            // Supplier information
            $table->unsignedBigInteger('account_of')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('supplier_commission', 10, 2)->nullable();

            // Broker information (up to 3 brokers)
            $table->unsignedBigInteger('broker_one_id')->nullable();
            $table->decimal('broker_one_commission', 10, 2)->nullable();
            $table->unsignedBigInteger('broker_two_id')->nullable();
            $table->decimal('broker_two_commission', 10, 2)->nullable();
            $table->unsignedBigInteger('broker_three_id')->nullable();
            $table->decimal('broker_three_commission', 10, 2)->nullable();

            // Product information
            $table->unsignedBigInteger('product_id');
            $table->enum('line_type', ['bari', 'choti'])->nullable();
            $table->integer('bag_weight')->nullable(); // in kg
            $table->decimal('bag_rate', 10, 2)->nullable();

            // Delivery information
            $table->date('delivery_date');
            $table->integer('credit_days')->nullable();
            $table->text('delivery_address');

            // Rate information
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('rate_per_mound', 10, 2);
            $table->decimal('rate_per_100kg', 10, 2);

            // Quantity information
            $table->enum('calculation_type', ['trucks', 'quantity']);
            $table->integer('no_of_trucks')->nullable();
            $table->decimal('total_quantity', 12, 2)->nullable(); // in kg
            $table->decimal('min_quantity', 12, 2); // in kg
            $table->decimal('max_quantity', 12, 2); // in kg
            $table->integer('no_of_bags');

            // Other information
            $table->boolean('is_replacement')->default(false);
            $table->decimal('weighbridge_from', 10, 2)->nullable();
            $table->text('remarks')->nullable();

            // Status and timestamps
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('company_location_id')->references('id')->on('company_locations');
            $table->foreign('sauda_type_id')->references('id')->on('sauda_types');
            $table->foreign('account_of')->references('id')->on('users');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('broker_one_id')->references('id')->on('brokers');
            $table->foreign('broker_two_id')->references('id')->on('brokers');
            $table->foreign('broker_three_id')->references('id')->on('brokers');
            $table->foreign('product_id')->references('id')->on('products');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');





















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
