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
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->enum("am_approval_status", ["approved", "rejected", "reverted", "pending"]);
            $table->integer("am_change_made")->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            //
        });
    }
};
