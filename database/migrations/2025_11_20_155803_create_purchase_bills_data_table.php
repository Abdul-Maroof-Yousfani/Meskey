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
        Schema::create('purchase_bills_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("purchase_bill_id")->constrained("purchase_bills")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->string("description")->nullable();
            $table->float("qty");
            $table->float("rate");
            $table->float("gross_amount");
            $table->float("net_amount");
            $table->float("tax_percent");
            $table->float("tax_amount");
            $table->float("discount_percent");
            $table->float("discount_amount");
            $table->float("deduction");
            $table->float("deduction_per_piece");
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
        Schema::dropIfExists('purchase_bills_data');
    }
};
