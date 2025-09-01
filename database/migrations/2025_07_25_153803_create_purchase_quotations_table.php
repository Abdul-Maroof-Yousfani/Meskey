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
        Schema::create('purchase_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->string('purchase_quotation_no')->unique();
            $table->unsignedBigInteger('company_id');
            $table->date('quotation_date');
            $table->unsignedBigInteger('location_id');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotations');
    }
};
