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
        Schema::table('purchase_bills_data', function (Blueprint $table) {
            $table->foreignId("purchase_order_receiving_data_id")->nullable()->constrained("purchase_order_receiving_data")->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bills_data', function (Blueprint $table) {
            //
        });
    }
};
