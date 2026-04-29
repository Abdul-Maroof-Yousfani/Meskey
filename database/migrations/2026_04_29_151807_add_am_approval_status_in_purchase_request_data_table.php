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
        Schema::table('purchase_request_data', function (Blueprint $table) {
            $table->enum("am_approval_status", ["approved", "pending", "reverted", "rejected"])->default("pending");
            $table->integer("am_change_made")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_data', function (Blueprint $table) {
            //
        });
    }
};
