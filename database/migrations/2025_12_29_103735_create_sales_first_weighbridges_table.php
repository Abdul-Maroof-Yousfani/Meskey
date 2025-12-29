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
        Schema::create('sales_first_weighbridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('delivery_order_id')->constrained('delivery_order')->onDelete('cascade');
            $table->foreignId('truck_type_id')->constrained('arrival_truck_types')->onDelete('cascade');
            $table->decimal('first_weight', 10, 2);
            $table->decimal('weighbridge_amount', 10, 2);
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
        Schema::dropIfExists('sales_first_weighbridges');
    }
};
