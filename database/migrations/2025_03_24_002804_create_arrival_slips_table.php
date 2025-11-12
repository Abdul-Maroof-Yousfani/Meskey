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
        Schema::create('arrival_slips', function (Blueprint $table) {
            $table->id();
            $table->string('unique_no')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_ticket_id');
            $table->unsignedBigInteger('creator_id');
            $table->string('remark');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_slips');
    }
};
