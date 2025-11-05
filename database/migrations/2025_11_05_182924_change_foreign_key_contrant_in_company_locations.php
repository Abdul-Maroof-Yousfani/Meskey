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
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropForeign(['city_id']);

            $table->dropIndex('company_locations_city_id_foreign');

            $table->foreign('city_id', 'fk_company_locations_city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropForeign('fk_company_locations_city_id');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
    }
};
