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
        Schema::create('export_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_order_id')->nullable()->constrained('export_orders')->onDelete('cascade');
            $table->foreignId('buyer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('export_form_e_id')->nullable()->constrained('export_form_es')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->json('export_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_delivery_orders');
    }
};
