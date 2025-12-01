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
        Schema::create('sales_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no')->unique();
            $table->date("date");
            $table->string("customer");
            $table->enum("contract_type", ["thadda", "pohanch"])->default("thadda");
            $table->enum("status", ["pending", "approved", "rejected"])->default("pending");
            $table->foreignId("company_id")->constrained("companies")->cascadeOnDelete();
            $table->integer("created_by");
            $table->string("contact_person");
            $table->string("remarks");
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
        Schema::dropIfExists('inquiry');
    }
};
