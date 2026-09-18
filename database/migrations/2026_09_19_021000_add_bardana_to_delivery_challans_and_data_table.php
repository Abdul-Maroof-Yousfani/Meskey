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
        if (Schema::hasTable('delivery_challans') && !Schema::hasColumn('delivery_challans', 'is_bardana')) {
            Schema::table('delivery_challans', function (Blueprint $table) {
                $table->boolean('is_bardana')->default(0)->after('sauda_type');
            });
        }

        if (Schema::hasTable('delivery_challan_data')) {
            Schema::table('delivery_challan_data', function (Blueprint $table) {
                if (!Schema::hasColumn('delivery_challan_data', 'bag_weight')) {
                    $table->decimal('bag_weight', 14, 4)->default(0)->after('no_of_bags');
                }
                if (!Schema::hasColumn('delivery_challan_data', 'total_bag_weight')) {
                    $table->decimal('total_bag_weight', 14, 4)->default(0)->after('bag_weight');
                }
                if (!Schema::hasColumn('delivery_challan_data', 'billed_qty')) {
                    $table->decimal('billed_qty', 14, 4)->nullable()->after('total_bag_weight');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('delivery_challans') && Schema::hasColumn('delivery_challans', 'is_bardana')) {
            Schema::table('delivery_challans', function (Blueprint $table) {
                $table->dropColumn('is_bardana');
            });
        }

        if (Schema::hasTable('delivery_challan_data')) {
            Schema::table('delivery_challan_data', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('delivery_challan_data', 'bag_weight')) {
                    $columns[] = 'bag_weight';
                }
                if (Schema::hasColumn('delivery_challan_data', 'total_bag_weight')) {
                    $columns[] = 'total_bag_weight';
                }
                if (Schema::hasColumn('delivery_challan_data', 'billed_qty')) {
                    $columns[] = 'billed_qty';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
