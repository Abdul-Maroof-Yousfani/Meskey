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
         Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_request_no')->unique();
            $table->date('purchase_date');
            $table->unsignedBigInteger('location_id');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->enum('purchase_request_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = approved');
            $table->string('approved_user_name')->nullable();
            $table->enum('status', ['1', '0'])->default('1')->comment('1 = active, 0 = inactive');
            $table->enum('po_status', ['1', '2'])->default('1')->comment('1 = pending, 2 = complete');
            $table->softDeletes(); // Adds `deleted_at` column for soft deletes
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
