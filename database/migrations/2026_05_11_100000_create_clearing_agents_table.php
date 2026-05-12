<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearing_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('unique_no');
            $table->string('name');
            $table->decimal('rate', 15, 2)->default(0);
            $table->string('owner_name');
            $table->string('owner_mobile_no');
            $table->string('owner_cnic_no');
            $table->string('next_to_kin')->nullable();
            $table->string('next_to_kin_mobile_no')->nullable();
            $table->string('owner_bank_detail')->nullable();
            $table->string('company_bank_detail')->nullable();
            $table->string('prefix')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('ntn')->nullable();
            $table->string('stn')->nullable();
            $table->string('attachment')->nullable();
            $table->json('company_location_ids')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearing_agents');
    }
};
