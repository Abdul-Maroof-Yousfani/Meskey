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
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->date('jv_date');
            $table->string('jv_no')->unique();
            $table->text('description')->nullable();
            $table->string('username')->nullable();
            $table->string('status')->default('active');
            $table->string('jv_status')->default('pending');
            $table->string('approve_username')->nullable();
            $table->string('delete_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_vouchers');
    }
};
