<?php
// database/migrations/2024_01_01_000002_create_machine_plan_setting_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('machine_plan_setting_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('machine_plan_setting_id');
            $table->unsignedBigInteger('production_machine_id');
            $table->decimal('hours', 8, 2)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('machine_plan_setting_id')->references('id')->on('machine_plan_settings')->onDelete('cascade');
            $table->foreign('production_machine_id')->references('id')->on('production_machines')->onDelete('cascade');

            $table->unique(['machine_plan_setting_id', 'production_machine_id'], 'unique_machine_plan_setting');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_plan_setting_items');
    }
};