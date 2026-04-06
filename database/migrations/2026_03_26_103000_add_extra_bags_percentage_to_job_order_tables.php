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
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            $table->decimal('extra_bags_percentage', 8, 2)->nullable()->after('extra_bags');
        });

        Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
            $table->decimal('extra_bags_percentage', 8, 2)->nullable()->after('extra_bags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            $table->dropColumn('extra_bags_percentage');
        });

        Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
            $table->dropColumn('extra_bags_percentage');
        });
    }
};
