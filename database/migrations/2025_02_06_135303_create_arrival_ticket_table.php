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
            $table->string('supplier_name')->nullable();
            $table->string('broker_name')->nullable();
            $table->string('accounts_off_name')->nullable();
            $table->unsignedBigInteger('decision_id')->nullable();

            $table->unsignedBigInteger('truck_type_id')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('sample_money')->nullable();
            $table->string('bilty_no')->nullable();
            $table->string('bags')->nullable();
            $table->string('station_id')->nullable();
            $table->date('loading_date')->nullable();
            $table->string('loading_weight')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('first_weight')->nullable();
            $table->string('second_weight')->nullable();
            $table->string('net_weight')->nullable();
            $table->softDeletes();
            $table->timestamps();

            //extra for stages status

            $table->enum('first_qc_status', ['reinspection', 'pending', 'rejected', 'approved'])->nullable();
            $table->enum('location_transfer_status', ['pending', 'transfered'])->nullable();
            $table->enum('second_qc_status', ['reinspection', 'pending', 'rejected', 'approved'])->nullable();
            $table->enum('document_approval_status', ['half_approved', 'fully_approved'])->nullable();
            $table->enum('second_weighbridge_status', ['pending', 'completed'])->nullable();
            $table->enum('arrival_slip_status', ['pending', 'generated'])->nullable();

            $table->foreign('station_id')->references('id')->on('stations');
            $table->foreign('decision_id')->references('id')->on('users');
            $table->foreign('truck_type_id')->references('id')->on('arrival_truck_types');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_ticket');
    }
};
