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
        Schema::create('machine_time_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('production_voucher_machine_time_id');
            $table->time('from');
            $table->time('to')->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('production_voucher_machine_time_id', 'mtb_pvm_time_id_fk')
                  ->references('id')->on('production_voucher_machine_times')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_time_breakdowns');
    }
};
