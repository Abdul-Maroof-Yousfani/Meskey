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
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->decimal('stuffing_maunds', 10, 3)->default(0.000)->nullable()->change();
            $table->integer('no_of_containers')->default(0)->nullable()->change();
            $table->decimal('stuffing_in_container', 10, 3)->default(0.000)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_order_packing_items', function (Blueprint $table) {
            $table->decimal('stuffing_maunds', 10, 3)->default(0.000)->nullable(false)->change();
            $table->integer('no_of_containers')->default(0)->nullable(false)->change();
            $table->decimal('stuffing_in_container', 10, 3)->default(0.000)->nullable(false)->change();
        });
    }
};
