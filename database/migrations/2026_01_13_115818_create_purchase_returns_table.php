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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->unique();
            $table->date('date');
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_bill_id');
            $table->unsignedBigInteger('company_location_id');
            $table->unsignedBigInteger('created_by');
            $table->text('remarks')->nullable();
            $table->enum('am_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('am_change_made')->default(false);
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('purchase_bill_id')->references('id')->on('purchase_bills');
            $table->foreign('company_location_id')->references('id')->on('company_locations');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
