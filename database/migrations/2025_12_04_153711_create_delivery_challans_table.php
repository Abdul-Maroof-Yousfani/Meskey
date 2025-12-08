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
        Schema::create('delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            // $table->foreignId("delivery_order_id")->constrained("delivery_order")->cascadeOnDelete();
            $table->string("reference_number");
            $table->date("dispatch_date");
            $table->string("dc_no");
            $table->enum("sauda_type", ["pohanch", "x-mill"]);
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->constrained("arrival_locations")->cascadeOnDelete();
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();
            $table->string("labour");
            $table->float("labour_amount");
            $table->string("transporter");
            $table->float("transporter_amount");
            $table->string("inhouse-weighbridge");
            $table->string("weighbridge-amount");
            $table->string("remarks");
            $table->foreignId("created_by_id")->constrained("users")->cascadeOnDelete();
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
        Schema::dropIfExists('delivery_challan');
    }
};
