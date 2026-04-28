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
        Schema::table('quotation_packing_items', function (Blueprint $table) {
            $table->decimal('stuffing_in_container', 15, 4)->nullable()->default(0)->after('total_kgs');
            $table->integer('no_of_containers')->nullable()->default(0)->after('stuffing_in_container');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['stuffing_in_container', 'stuffing_maunds', 'no_of_containers']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('stuffing_in_container', 15, 4)->nullable()->default(0);
            $table->decimal('stuffing_maunds', 15, 4)->nullable()->default(0);
            $table->integer('no_of_containers')->nullable()->default(0);
        });

        Schema::table('quotation_packing_items', function (Blueprint $table) {
            $table->dropColumn(['stuffing_in_container', 'no_of_containers']);
        });
    }
};
