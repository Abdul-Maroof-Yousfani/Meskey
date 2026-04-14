<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_challans', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_challans', 'type')) {
                $table->string('type')->default('sale_delivery_challan')->after('dc_no');
            }
        });

        DB::table('delivery_challans')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => 'sale_delivery_challan']);
    }

    public function down(): void
    {
        Schema::table('delivery_challans', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_challans', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
