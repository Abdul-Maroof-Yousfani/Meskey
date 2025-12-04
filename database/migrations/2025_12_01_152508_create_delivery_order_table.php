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
        Schema::create('delivery_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            $table->foreignId("so_id")->constrained("sales_orders")->cascadeOnDelete();
            $table->float("advance_amount")->nullable()->default(0);
            $table->float("withhold_amount")->nullable()->default(0);
            $table->float("withhold_for_rv_id")->nullable()->constrained("receipt_vouchers")->cascadeOnDelete();
            $table->date("dispatch_date");
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->constrained("company_location_id")->cascadeOnDelete();
            $table->foreignId("subarrival_id")->constrained("arrival_sub_locations")->cascadeOnDelete();
            $table->string("reference_no");
            $table->enum("sauda_type", ["pohanch", "x-mill"]);
            $table->string("line_desc")->nullable();

            // $table->float("so_amount");
            // $table->float("percentage")->nullable();
            $table->string("am_approval_status")->default("pending");
            $table->string("am_change_made")->default(1);            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order');
    }
};
