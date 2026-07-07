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
        Schema::create('export_order_addendums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_order_id');
            $table->text('remarks')->nullable();
            $table->string('am_approval_status')->default('approved');
            $table->boolean('am_change_made')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('export_order_id')->references('id')->on('export_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_order_addendums');
    }
};
