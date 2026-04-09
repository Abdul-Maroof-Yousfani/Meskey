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
        Schema::create('rejection_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->date('date');
            $table->string('reference_no')->nullable();
            $table->string('truck_no')->nullable();
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('am_approval_status')->default('pending');
            $table->integer("am_change_made")->default(1);
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('rejection_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rejection_return_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('weight', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('rejection_return_id')->references('id')->on('rejection_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rejection_return_items');
        Schema::dropIfExists('rejection_returns');
    }
};
