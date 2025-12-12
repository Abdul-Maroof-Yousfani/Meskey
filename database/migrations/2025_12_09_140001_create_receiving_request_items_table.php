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
        Schema::create('receiving_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_request_id')->constrained('receiving_requests')->onDelete('cascade');
            $table->foreignId('delivery_challan_data_id')->nullable()->constrained('delivery_challan_data')->onDelete('set null');
            $table->foreignId('item_id')->nullable()->constrained('products')->onDelete('set null');
            
            // Weight Information
            $table->string('item_name')->nullable();
            $table->decimal('dispatch_weight', 15, 2)->default(0); // qty from DC
            $table->decimal('receiving_weight', 15, 2)->default(0); // user input
            $table->decimal('difference_weight', 15, 2)->default(0); // dispatch - receiving
            $table->decimal('seller_portion', 15, 2)->default(0); // user input
            $table->decimal('remaining_amount', 15, 2)->default(0); // difference - seller portion
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_request_items');
    }
};

