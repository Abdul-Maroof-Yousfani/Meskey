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
        Schema::create('production_voucher_machines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_voucher_id');
            $table->unsignedBigInteger('production_machine_id');
            $table->timestamps();

            $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->onDelete('cascade');
            $table->foreign('production_machine_id')->references('id')->on('production_machines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_voucher_machines');
    }
};
