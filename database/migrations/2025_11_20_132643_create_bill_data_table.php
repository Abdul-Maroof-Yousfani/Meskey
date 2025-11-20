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
        Schema::create('bill_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("bill_id")->constrained("bills")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->foreignId("purchase_order_receiving_data_id")->constrained("purchase_order_receiving_data")->cascadeOnDelete();
            $table->string("description")->nullable();
            $table->float("qty");
            $table->float("rate");
            $table->float("gross_amount");
            $table->foreignId("tax_id")->constrained("taxes")->cascadeOnDelete();
            $table->float("net_amount");
            $table->float("discount_percent");
            $table->float("discount_amount");
            $table->float("deduction");
            $table->float("final_amount");
            $table->enum("bill_status", ["pending", "completed"])->default("pending");
            $table->string("am_approval_status");
            $table->string("am_change_mode");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_data');
    }
};
