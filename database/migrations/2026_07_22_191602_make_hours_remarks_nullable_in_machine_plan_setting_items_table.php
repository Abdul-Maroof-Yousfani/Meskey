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
        Schema::table('machine_plan_setting_items', function (Blueprint $table) {
            $table->decimal('hours', 8, 2)->nullable()->change();
            $table->text('remarks')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_plan_setting_items', function (Blueprint $table) {
            $table->decimal('hours', 8, 2)->default(0)->change();
            // remarks was already nullable, but reverting here to original state
            $table->text('remarks')->nullable()->change();
        });
    }
};
