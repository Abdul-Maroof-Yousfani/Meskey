<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('purchase_item_approve', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_data_id');
             $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedTinyInteger('status_id')->default(1)->comment('1 = Pending, 2 = Approved');
            $table->timestamps();

            $table->foreign('purchase_request_data_id')->references('id')->on('purchase_request_data')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_item_approve');
    }
};
