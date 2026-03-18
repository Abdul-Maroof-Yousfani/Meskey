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
            $table->integer('brand_id')->nullable()->change();
            $table->string('construction_per_square_inch', 11, 2)->nullable()->change();
            $table->string('micron')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_data', function (Blueprint $table) {
            $table->integer('brand_id')->nullable(false)->change();
            $table->string('construction_per_square_inch', 11, 2)->nullable(false)->change();
            $table->string('micron')->nullable(false)->change();
        });
    }
};
