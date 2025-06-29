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
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('approval_modules')->onDelete('cascade');
            $table->foreignId('record_id')->comment('ID of the record in the related module table');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->enum('action', ['approved', 'rejected'])->default('approved')->comment('approved, rejected');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['module_id', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};
