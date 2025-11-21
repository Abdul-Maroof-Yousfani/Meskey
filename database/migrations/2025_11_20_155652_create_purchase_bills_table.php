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
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId("supplier_id")->constrained("suppliers")->cascadeOnDelete();
            $table->foreignId("purchase_order_receiving_id")->constrained("purchase_order_receivings")->cascadeOnDelete();
            $table->foreignId("purchase_request_id")->nullable()->constrained("purchase_requests")->cascadeOnDelete();
            $table->foreignId("purchase_order_id")->nullable()->constrained("purchase_orders")->cascadeOnDelete();
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->string("bill_no");
            $table->string("reference_no");
            $table->enum("status", ["active", "pending"]);
            $table->string("description");
            $table->foreignId("created_by")->constrained("users")->cascadeOnDelete();
            $table->string("am_approval_status");
            $table->string("am_change_made");
            $table->date("bill_date");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
    }
};
