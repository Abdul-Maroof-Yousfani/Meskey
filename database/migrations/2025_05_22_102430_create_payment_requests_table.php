<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('arrival_purchase_orders')->onDelete('cascade');
            $table->enum('request_type', ['payment', 'freight_payment']);
            $table->string('supplier_name');
            $table->decimal('contract_rate', 15, 2);
            $table->decimal('min_contract_range', 15, 2);
            $table->decimal('max_contract_range', 15, 2);
            $table->boolean('is_loading')->default(false);
            $table->string('truck_no')->nullable();
            $table->date('loading_date')->nullable();
            $table->string('bilty_no')->nullable();
            $table->string('station')->nullable();
            $table->integer('no_of_bags')->default(0);
            $table->decimal('loading_weight', 15, 2)->default(0);
            $table->decimal('avg_rate', 15, 2)->default(0);

            $table->decimal('bag_weight', 15, 2)->default(0);
            $table->decimal('bag_weight_total', 15, 2)->default(0);
            $table->decimal('bag_weight_amount', 15, 2)->default(0);

            $table->decimal('bag_rate', 15, 2)->default(0);
            $table->decimal('bag_rate_amount', 15, 2)->default(0);

            $table->decimal('loading_weighbridge_amount', 15, 2)->default(0);

            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->decimal('payment_request_amount', 15, 2);
            $table->decimal('advance_freight', 15, 2)->default(0);
            $table->decimal('freight_pay_request_amount', 15, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_request_sampling_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('slab_type_id')->nullable()->constrained('product_slab_types')->onDelete('set null');
            $table->string('name');
            $table->decimal('checklist_value', 15, 2);
            $table->decimal('suggested_deduction', 15, 2);
            $table->decimal('applied_deduction', 15, 2);
            $table->string('deduction_type');
            $table->decimal('deduction_amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_request_sampling_results');
        Schema::dropIfExists('payment_requests');
    }
};
