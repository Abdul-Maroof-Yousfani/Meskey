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
        Schema::create('receiving_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_challan_id')->constrained('delivery_challans')->onDelete('cascade');
            
            // DC Information
            $table->string('dc_no')->nullable();
            $table->date('dc_date')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('bilty')->nullable();
            
            // DC Details
            $table->string('labour')->nullable();
            $table->string('transporter')->nullable();
            $table->string('inhouse_weighbridge')->nullable();
            $table->decimal('labour_amount', 15, 2)->default(0);
            $table->decimal('transporter_amount', 15, 2)->default(0);
            $table->decimal('weighbridge_amount', 15, 2)->default(0);
            $table->decimal('inhouse_weighbridge_amount', 15, 2)->default(0);
            
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_requests');
    }
};

