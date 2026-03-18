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
        Schema::table('transporters', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->change();
            $table->string('owner_mobile_no')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->string('owner_name')->nullable(false)->change();
            $table->string('owner_mobile_no')->nullable(false)->change();
        });
    }
};
