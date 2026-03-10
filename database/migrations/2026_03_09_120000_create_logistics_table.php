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
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('sale_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->string('loading_request')->nullable();
            $table->string('so_no')->nullable();
            $table->decimal('so_qty', 15, 2)->nullable();
            $table->string('commodity')->nullable();
            $table->string('sauda_type')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('location')->nullable();
            $table->string('gala')->nullable();
            $table->string('customer')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('logistics_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_id')->constrained('logistics')->onDelete('cascade');
            $table->string('rate_type')->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->string('transporter')->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_items');
        Schema::dropIfExists('logistics');
    }
};
