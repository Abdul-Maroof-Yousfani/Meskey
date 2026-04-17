<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_ladings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_delivery_challan_id')->constrained('delivery_challans')->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_order')->nullOnDelete();
            $table->foreignId('export_order_id')->nullable()->constrained('export_orders')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('bill_no')->unique();
            $table->date('bill_date')->nullable();
            $table->string('carrier_name')->nullable();
            $table->date('shipped_on_board_date')->nullable();
            $table->string('charter_party_dated')->nullable();
            $table->longText('cautions_text')->nullable();
            $table->string('place_of_issue')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->json('goods_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('export_delivery_challan_id', 'bol_unique_delivery_challan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_ladings');
    }
};
