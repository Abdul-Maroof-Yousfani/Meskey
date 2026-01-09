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
        Schema::table('loading_slips', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_order_id')->nullable(); // add column first

            $table->foreign('delivery_order_id') // then add foreign key
                ->references('id')
                ->on('delivery_orders')
                ->onDelete('set null'); // optional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_slips', function (Blueprint $table) {
            //
        });
    }
};
