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
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->tinyInteger('calculation_base_type')
                ->default(3)
                ->nullable()
                ->after('description')
                ->comment('1 = Percentage, 2 = KG, 3 = Price, 4 = Quantity, etc...');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_slab_types', function (Blueprint $table) {
            $table->dropColumn('calculation_base_type');
        });
    }
};
