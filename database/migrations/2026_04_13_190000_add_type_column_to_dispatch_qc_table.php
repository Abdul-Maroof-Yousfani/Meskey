<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispatch_qc') && !Schema::hasColumn('dispatch_qc', 'type')) {
            Schema::table('dispatch_qc', function (Blueprint $table) {
                $table->string('type')->default('sale_dispatch_qc')->after('id');
            });
        }

        if (Schema::hasTable('dispatch_qc') && Schema::hasColumn('dispatch_qc', 'type')) {
            DB::table('dispatch_qc')->whereNull('type')->update(['type' => 'sale_dispatch_qc']);
            DB::table('dispatch_qc')->where('type', '')->update(['type' => 'sale_dispatch_qc']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dispatch_qc') && Schema::hasColumn('dispatch_qc', 'type')) {
            Schema::table('dispatch_qc', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
