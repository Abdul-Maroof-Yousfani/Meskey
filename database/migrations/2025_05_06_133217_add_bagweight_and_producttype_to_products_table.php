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
        Schema::table('products', function (Blueprint $table) {
            $table->float('bag_weight_for_purchasing')->nullable()->after('description');
            $table->enum('product_type', ['raw_material', 'finish_good'])->default('raw_material')->after('bag_weight_for_purchasing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['bag_weight', 'product_type']);
        });
    }
};
