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
        Schema::create('purchase_sampling_requests', function (Blueprint $table) {
           $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_purchase_order_id');
            $table->unsignedBigInteger('arrival_product_id')->nullable();

            $table->enum('sampling_type', ['initial', 'inner'])->default('initial');
            $table->enum('is_re_sampling', ['yes', 'no'])->default('no');
            $table->string('remark')->nullable();
            $table->enum('is_done', ['yes', 'no'])->default('no');
            $table->unsignedBigInteger('done_by')->nullable();

            $table->enum('is_request_by_purchaser', ['yes', 'no'])->default('no');
            $table->string('purchaser_remarks')->nullable();
            $table->enum('is_auto_approved', ['yes', 'no'])->default('no');

            $table->enum('is_resampling_made', ['yes', 'no'])->default('no');
            $table->string('party_ref_no')->nullable();
            $table->unsignedBigInteger('sample_taken_by')->nullable();

            $table->decimal('lumpsum_deduction', 10, 2)->default(0);
            $table->decimal('lumpsum_deduction_kgs', 10, 2)->default(0);
            $table->tinyInteger('is_lumpsum_deduction')->default(0)
                ->comment('Switch state for lumpsum deduction');
            $table->tinyInteger('decision_making')->default(0);

            $table->string('approved_remarks')->nullable();
            $table->enum('approved_status', ['pending', 'approved', 'rejected', 'resampling'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('arrival_purchase_order_id')->references('id')->on('arrival_purchase_orders')->onDelete('cascade');
            $table->foreign('done_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('arrival_product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('sample_taken_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_sampling_requests');
    }
};
