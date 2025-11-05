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
        Schema::table('arrival_sub_locations', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);

            $table->foreign('company_location_id')->references('id')->on('company_locations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_sub_locations', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);

            $table->foreign('company_location_id')->references('id')->on('arrival_locations')->onDelete('cascade');
        });
    }
};
