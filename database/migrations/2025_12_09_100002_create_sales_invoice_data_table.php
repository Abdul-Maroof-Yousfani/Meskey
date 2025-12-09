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
        Schema::create('sales_invoice_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId("sales_invoice_id")->constrained("sales_invoices")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("products")->cascadeOnDelete();
            $table->float("packing")->default(0);
            $table->float("no_of_bags")->default(0);
            $table->float("qty")->default(0);
            $table->float("rate")->default(0);
            $table->float("gross_amount")->default(0);
            $table->float("discount_percent")->default(0);
            $table->float("discount_amount")->default(0);
            $table->float("amount")->default(0);
            $table->float("gst_percent")->default(0);
            $table->float("gst_amount")->default(0);
            $table->float("net_amount")->default(0);
            $table->foreignId("dc_data_id")->nullable()->constrained("delivery_challan_data")->cascadeOnDelete();
            $table->text("line_desc")->nullable();
            $table->string("truck_no")->nullable();
            $table->text("description")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_data');
    }
};

