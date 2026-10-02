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
        Schema::table('production_recipes_items', function (Blueprint $table) {
            $table->dropColumn([
                'key',
                'slug',
                'type',
            ]);
            $table->unsignedBigInteger('production_attribute_id')
                ->nullable()
                ->after('production_recipe_id');

            $table->foreign('production_attribute_id')
                ->references('id')
                ->on('prodction_attribute')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_recipes_items', function (Blueprint $table) {
            $table->string('key', 191);
            $table->string('slug', 191)->unique();
            $table->string('type', 50)->default('text');
            $table->dropForeign(['production_attribute_id']);
            $table->dropColumn('production_attribute_id');
        });
    }
};
