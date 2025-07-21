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
        Schema::create('purchase_request_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('qty', 15, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('purchase_request_id')
            ->references('id')->on('purchase_requests')
            ->onDelete('cascade'); // Cascade on hard delete
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_data');
    }
};
