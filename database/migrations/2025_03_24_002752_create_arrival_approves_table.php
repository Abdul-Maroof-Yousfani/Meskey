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
        Schema::create('arrival_approves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('arrival_ticket_id');
            $table->unsignedBigInteger('creator_id');
            $table->text('remark')->nullable();

            $table->string('gala_name')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('filling_bags_no')->nullable();
            $table->unsignedBigInteger('bag_type_id')->nullable();
            $table->unsignedBigInteger('bag_condition_id')->nullable();
            $table->unsignedBigInteger('bag_packing_id')->nullable();
            $table->enum('bag_packing_approval', ['Half Approved', 'Full Approved'])->nullable();
            $table->integer('total_receivings')->nullable();
            $table->integer('total_bags')->nullable();
            $table->integer('total_rejection')->nullable();
            $table->enum('amanat', ['Yes', 'No'])->default('No');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users');
            $table->foreign('bag_type_id')->references('id')->on('bag_types');
            $table->foreign('bag_condition_id')->references('id')->on('bag_conditions');
            $table->foreign('bag_packing_id')->references('id')->on('bag_packings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_approves');
    }
};
