<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_second_weighbridges') && !Schema::hasColumn('sales_second_weighbridges', 'type')) {
            Schema::table('sales_second_weighbridges', function (Blueprint $table) {
                $table->string('type')->default('sale_order')->after('id');
            });
        }

        if (Schema::hasTable('sales_second_weighbridges') && Schema::hasColumn('sales_second_weighbridges', 'type')) {
            DB::table('sales_second_weighbridges')->whereNull('type')->update(['type' => 'sale_order']);
            DB::table('sales_second_weighbridges')->where('type', '')->update(['type' => 'sale_order']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_second_weighbridges') && Schema::hasColumn('sales_second_weighbridges', 'type')) {
            Schema::table('sales_second_weighbridges', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
