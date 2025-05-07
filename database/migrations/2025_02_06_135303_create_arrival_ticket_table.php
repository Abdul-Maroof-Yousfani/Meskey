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
        Schema::create('arrival_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('qc_product')->nullable();

            $table->string('supplier_name')->nullable();
            $table->string('broker_name')->nullable();

            $table->unsignedBigInteger('decision_id')->nullable();
            $table->string('accounts_of_id')->nullable();

            $table->unsignedBigInteger('arrival_purchase_order_id')->nullable();
            $table->unsignedBigInteger('sauda_type_id')->nullable();
            $table->tinyInteger('decision_making')->default(0);

            $table->unsignedBigInteger('truck_type_id')->nullable();
            $table->string('sample_money')->nullable();
            $table->string('sample_money_type')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('bilty_no')->nullable();
            $table->string('bags')->nullable();
            $table->foreignId('station_id')->nullable()->constrained('stations');
            $table->date('loading_date')->nullable();
            $table->string('loading_weight')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('first_weight')->nullable();
            $table->string('second_weight')->nullable();
            $table->string('net_weight')->nullable();
            $table->string('arrived_net_weight')->nullable();
            $table->decimal('lumpsum_deduction', 10, 2)->default(0);
            $table->decimal('lumpsum_deduction_kgs', 10, 2)->default(0);
            $table->tinyInteger('is_lumpsum_deduction')->default(0)->comment('Switch state for lumpsum deduction');

            $table->softDeletes();
            $table->timestamps();

            //extra for stages status
            $table->enum('first_qc_status', ['resampling', 'pending', 'rejected', 'approved'])->nullable();
            $table->tinyInteger('bilty_return_confirmation')->default(0);
            $table->enum('location_transfer_status', ['pending', 'transfered'])->nullable();
            $table->enum('second_qc_status', ['resampling', 'pending', 'rejected', 'approved'])->nullable();
            $table->enum('document_approval_status', ['half_approved', 'fully_approved'])->nullable();
            $table->enum('first_weighbridge_status', ['pending', 'completed'])->nullable();
            $table->enum('second_weighbridge_status', ['pending', 'completed'])->nullable();
            $table->string('freight_status')->nullable();
            $table->enum('arrival_slip_status', ['pending', 'generated'])->nullable();

            $table->foreign('decision_id')->references('id')->on('users');
            $table->foreign('truck_type_id')->references('id')->on('arrival_truck_types');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('accounts_of_id')->references('id')->on('users');
            $table->foreign('arrival_purchase_order_id')->references('id')->on('arrival_purchase_orders');
            $table->foreign('sauda_type_id')->references('id')->on('sauda_types');
            $table->foreign('qc_product')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_tickets');
    }
};
