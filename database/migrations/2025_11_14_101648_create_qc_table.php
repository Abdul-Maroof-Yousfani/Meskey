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
        Schema::create('qc', function (Blueprint $table) {
            $table->id();
            $table->foreignId("purchase_order_receiving_data_id")->constrained("purchase_order_receiving_data")->cascadeOnDelete();
            $table->float("accepted_quantity")->default(0)->nullable();
            $table->float("rejected_quantity")->default(0)->nullable();
            $table->float("deduction_per_bag")->default(0)->nullable();
            $table->boolean("is_qc_approved")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc');
    }
};
