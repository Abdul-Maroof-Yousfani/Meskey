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
        Schema::create('first_weighbridges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_ticket_id');
            $table->unsignedBigInteger('created_by');
            $table->string('remark')->nullable();
            $table->string('weight')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('first_weighbridges');
    }
};
