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
        Schema::table('loading_program_items', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_location_id')->nullable()->change();
            $table->unsignedBigInteger('sub_arrival_location_id')->nullable()->change();
            $table->unsignedBigInteger('brand_id')->nullable()->change();
            $table->string('packing')->nullable()->change();
            $table->decimal('qty', 15, 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_program_items', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_location_id')->nullable(false)->change();
            $table->unsignedBigInteger('sub_arrival_location_id')->nullable(false)->change();
            $table->unsignedBigInteger('brand_id')->nullable(false)->change();
            $table->string('packing')->nullable(false)->change();
            $table->decimal('qty', 15, 3)->nullable(false)->change();
        });
    }
};
