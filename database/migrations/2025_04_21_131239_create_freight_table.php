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
        Schema::create('freights', function (Blueprint $table) {
            $table->id();
            // $table->string('ticket_number');
            // $table->string('supplier');
            // $table->string('commodity');
            // $table->string('truck_number');
            // $table->string('billy_number');
            $table->decimal('estimated_freight', 10, 2)->nullable();
            $table->integer('loaded_weight')->default(0);
            $table->integer('arrived_weight')->default(0);
            $table->integer('difference')->default(0);
            $table->integer('exempted_weight')->default(0);
            $table->decimal('pq_rate', 10, 2)->nullable();
            $table->integer('net_shortage')->default(0);
            $table->decimal('shortage_weight_freight_deduction', 10, 2)->default(0);
            $table->decimal('freight_per_ton', 10, 2)->default(0);
            $table->decimal('kanta_golarchi_charges', 10, 2)->default(0);
            $table->decimal('other_labour_charges', 10, 2)->nullable();
            $table->decimal('other_deduction', 10, 2)->nullable();
            $table->decimal('unpaid_labor_charges', 10, 2)->nullable();
            $table->decimal('freight_written_on_billy', 10, 2)->default(0);
            $table->decimal('gross_freight_amount', 10, 2)->default(0);
            $table->decimal('net_freight', 10, 2)->default(0);
            $table->string('bilty_document')->nullable();
            $table->string('loading_weight_document')->nullable();
            $table->string('other_document')->nullable();
            $table->string('other_document_2')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            $table->softDeletes();
            // $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight');
    }
};
