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
        Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
            $table->decimal('packing_size', 10, 2)->nullable()->after('no_of_primary_bags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
            $table->dropColumn('packing_size');
        });
    }
};
