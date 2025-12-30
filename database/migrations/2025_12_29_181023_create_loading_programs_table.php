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
        Schema::create('loading_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('sale_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('delivery_order_id')->constrained('delivery_order')->onDelete('cascade');
            $table->json('company_locations')->nullable();
            $table->json('arrival_locations')->nullable();
            $table->json('sub_arrival_locations')->nullable();
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_programs');
    }
};
