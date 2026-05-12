<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearing_agent_owner_bank_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clearing_agent_id');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->string('branch_code');
            $table->string('account_title');
            $table->string('account_number');
            $table->timestamps();

            $table->foreign('clearing_agent_id')->references('id')->on('clearing_agents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearing_agent_owner_bank_details');
    }
};
