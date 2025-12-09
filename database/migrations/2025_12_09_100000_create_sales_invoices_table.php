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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            $table->text("invoice_address")->nullable();
            $table->foreignId("location_id")->constrained("company_locations")->cascadeOnDelete();
            $table->foreignId("arrival_id")->constrained("arrival_locations")->cascadeOnDelete();
            $table->string("si_no")->unique();
            $table->date("invoice_date");
            $table->string("reference_number")->nullable();
            $table->enum("sauda_type", ["pohanch", "x-mill"]);
            $table->text("remarks")->nullable();
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();
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
        Schema::dropIfExists('sales_invoices');
    }
};

