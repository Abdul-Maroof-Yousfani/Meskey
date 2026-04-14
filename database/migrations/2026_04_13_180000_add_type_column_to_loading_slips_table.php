<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loading_slips') && !Schema::hasColumn('loading_slips', 'type')) {
            Schema::table('loading_slips', function (Blueprint $table) {
                $table->string('type')->default('sale_loading_slip')->after('id');
            });
        }

        if (Schema::hasTable('loading_slips') && Schema::hasColumn('loading_slips', 'type')) {
            DB::table('loading_slips')->whereNull('type')->update(['type' => 'sale_loading_slip']);
            DB::table('loading_slips')->where('type', '')->update(['type' => 'sale_loading_slip']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loading_slips') && Schema::hasColumn('loading_slips', 'type')) {
            Schema::table('loading_slips', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
