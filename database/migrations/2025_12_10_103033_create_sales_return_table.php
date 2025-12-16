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
        Schema::create('sales_return', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            // $table->foreignId("si_id")->constrained("sales_invoices")->cascadeOnDelete();
            $table->string("sr_no");
            $table->date("date");
            $table->string("reference_number");
         
            $table->enum("contract_type", ["x-mill", "pohanch"]);
            $table->foreignId("company_location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_location_id")->constrained("arrival_locations")->cascadeOnDelete();
            $table->foreignId("storage_location_id")->constrained("arrival_sub_locations")->cascadeOnDelete();
            $table->foreignId("created_by")->nullable()->constrained("users")->cascadeOnDelete();
            $table->string("am_approval_status")->default("pending");
            $table->string("am_change_made")->default(1);
            $table->string("remarks")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return');
    }
};
