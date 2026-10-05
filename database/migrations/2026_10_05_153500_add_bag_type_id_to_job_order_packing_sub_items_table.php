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
        if (Schema::hasTable('job_order_packing_sub_items')) {
            Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
                if (!Schema::hasColumn('job_order_packing_sub_items', 'bag_type_id')) {
                    $table->unsignedBigInteger('bag_type_id')->nullable()->after('  ');
                    $table->foreign('bag_type_id')->references('id')->on('bag_types')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_order_packing_sub_items')) {
            Schema::table('job_order_packing_sub_items', function (Blueprint $table) {
                if (Schema::hasColumn('job_order_packing_sub_items', 'bag_type_id')) {
                    $table->dropForeign(['bag_type_id']);
                    $table->dropColumn('bag_type_id');
                }
            });
        }
    }
};
