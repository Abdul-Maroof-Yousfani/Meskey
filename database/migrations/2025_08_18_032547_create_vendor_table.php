<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('unique_no');
            $table->string('name');
            $table->string('company_name');
            $table->string('owner_name');
            $table->string('owner_mobile_no');
            $table->string('owner_cnic_no')->nullable();

            $table->string('next_to_kin')->nullable();
            $table->string('next_to_kin_mobile_no')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->string('owner_bank_detail')->nullable();
            $table->string('company_bank_detail')->nullable();
            $table->string('prefix')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->json('company_location_ids')->nullable();
            $table->string('ntn')->nullable();
            $table->string('stn')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
