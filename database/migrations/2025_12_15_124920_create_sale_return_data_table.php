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
        Schema::create('sale_return_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("sale_return_id")->constrained("sales_return")->cascadeOnDelete();
            $table->float("quantity");
            $table->float("rate");
            $table->float("gross_amount");
            $table->float("discount_percent");
            $table->float("discount_amount");
            $table->float("amount");
            $table->float("gst_percentage");
            $table->float("gst_amount");
            $table->float("net_amount");
            $table->float("line_desc");
            $table->float("truck_no");
            $table->string("packing");
            $table->unsignedInteger("no_of_bags");
            $table->foreignId("sale_invoice_data_id")->constrained("sales_invoice_data")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_data');
    }
};
