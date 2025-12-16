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
            $table->unsignedInteger("quantity");
            $table->unsignedInteger("rate");
            $table->unsignedInteger("gross_amount");
            $table->unsignedInteger("discount_percent");
            $table->unsignedInteger("discount_amount");
            $table->unsignedInteger("amount");
            $table->unsignedInteger("gst_percentage");
            $table->unsignedInteger("gst_amount");
            $table->unsignedInteger("net_amount");
            $table->unsignedInteger("line_desc");
            $table->unsignedInteger("truck_no");
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
