<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('user_type')->enum('super-admin', 'user', 'vendor', 'customer')->default('user');
            $table->string('email')->unique();
            $table->string('status')->enum('active', 'inactive')->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('current_company_id')->nullable();



            $table->rememberToken();
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
