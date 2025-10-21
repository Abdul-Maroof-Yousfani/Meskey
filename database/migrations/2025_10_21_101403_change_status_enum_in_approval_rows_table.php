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
        Schema::table('approval_rows', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'reverted', 'partial_approved'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('approval_rows', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'reverted'])->default('pending')->change();
        });
    }
};
