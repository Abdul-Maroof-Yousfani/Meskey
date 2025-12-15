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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->date("delivery_date");
            $table->string("reference_no");
            $table->foreignId("customer_id")->constrained("customers")->cascadeOnDelete();
            $table->foreignId("inquiry_id")->nullable()->constrained("sales_inquiries")->cascadeOnDelete();
            $table->enum("sauda_type", ["pohanch", "X-mill"]);
            $table->foreignId("payment_term_id")->constrained("payment_terms");
            $table->foreignId("pay_type_id")->constrained("pay_types")->cascadeOnDelete();
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();
            $table->string("contact_person")->nullable();
            $table->foreignId('arrival_location_id')->nullable()->constrained('arrival_locations');
            $table->foreignId('arrival_sub_location_id')->nullable()->constrained('arrival_sub_locations');
            $table->string("status")->default("pending");
            $table->string("am_approval_status")->default("pending");
            $table->string("am_change_made")->default(1);
            $table->date('order_date')->nullable();
            $table->decimal('token_money', 15, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('so_reference_no');
            $table->string("remarks");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order');
    }
};
