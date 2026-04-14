<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_qc')) {
            if (!Schema::hasColumn('sales_qc', 'type')) {
                Schema::table('sales_qc', function (Blueprint $table) {
                    $table->string('type')->default('sales_qc')->after('id');
                });
            }

            if (Schema::hasColumn('sales_qc', 'type')) {
                DB::table('sales_qc')->whereNull('type')->update(['type' => 'sales_qc']);
                DB::table('sales_qc')->where('type', '')->update(['type' => 'sales_qc']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_qc') && Schema::hasColumn('sales_qc', 'type')) {
            Schema::table('sales_qc', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};