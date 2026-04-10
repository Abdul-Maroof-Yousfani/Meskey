<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_first_weighbridges') && !Schema::hasTable('general_first_weighbridges')) {
            Schema::rename('sales_first_weighbridges', 'general_first_weighbridges');
        }

        if (Schema::hasTable('general_first_weighbridges') && !Schema::hasColumn('general_first_weighbridges', 'type')) {
            Schema::table('general_first_weighbridges', function (Blueprint $table) {
                $table->string('type')->default('sale_order')->after('id');
            });
        }

        if (Schema::hasTable('general_first_weighbridges') && Schema::hasColumn('general_first_weighbridges', 'type')) {
            DB::table('general_first_weighbridges')->whereNull('type')->update(['type' => 'sale_order']);
            DB::table('general_first_weighbridges')->where('type', '')->update(['type' => 'sale_order']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('general_first_weighbridges') && Schema::hasColumn('general_first_weighbridges', 'type')) {
            Schema::table('general_first_weighbridges', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        if (Schema::hasTable('general_first_weighbridges') && !Schema::hasTable('sales_first_weighbridges')) {
            Schema::rename('general_first_weighbridges', 'sales_first_weighbridges');
        }
    }
};
