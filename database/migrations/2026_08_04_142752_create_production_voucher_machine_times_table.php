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
        Schema::create('production_voucher_machine_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_voucher_id');
            $table->unsignedBigInteger('production_machine_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();

            $table->foreign('production_voucher_id', 'pvm_times_pv_id_fk')
                  ->references('id')->on('production_vouchers')->onDelete('cascade');
            $table->foreign('production_machine_id', 'pvm_times_pm_id_fk')
                  ->references('id')->on('production_machines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_voucher_machine_times');
    }
};
