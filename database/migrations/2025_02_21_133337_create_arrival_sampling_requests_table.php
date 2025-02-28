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
        Schema::create('arrival_sampling_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_ticket_id');
            $table->enum('sampling_type', ['initial', 'inner'])->default('initial');
            $table->enum('is_re_sampling', ['yes', 'no'])->default('no');
            $table->string('remark')->nullable();
            $table->enum('is_done', ['yes', 'no'])->default('no');
            $table->unsignedBigInteger('done_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('cascade');
            $table->foreign('done_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_sampling_requests');
    }
};
