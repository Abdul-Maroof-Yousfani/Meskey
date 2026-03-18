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
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('export_order_id')
                ->nullable()
                ->constrained('export_orders')
                ->nullOnDelete();

            $table->string('proforma_no')->nullable();
            $table->date('proforma_date')->nullable();
            $table->text('consigned_details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proformas');
    }
};
