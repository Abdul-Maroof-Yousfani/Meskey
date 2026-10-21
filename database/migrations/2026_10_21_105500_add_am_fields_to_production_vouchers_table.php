<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('production_vouchers', 'am_approval_status')) {
                $table->string('am_approval_status')->default('pending')->nullable()->after('status');
            }
            if (!Schema::hasColumn('production_vouchers', 'am_change_made')) {
                $table->tinyInteger('am_change_made')->default(1)->nullable()->after('am_approval_status');
            }
        });
        Schema::table('job_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('job_orders', 'am_approval_status')) {
                $table->string('am_approval_status')->default('pending')->nullable()->after('status');
            }
            if (!Schema::hasColumn('job_orders', 'am_change_made')) {
                $table->tinyInteger('am_change_made')->default(1)->nullable()->after('am_approval_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('production_vouchers', 'am_change_made')) {
                $table->dropColumn('am_change_made');
            }
            if (Schema::hasColumn('production_vouchers', 'am_approval_status')) {
                $table->dropColumn('am_approval_status');
            }
        });
        Schema::table('job_orders', function (Blueprint $table) {
            if (Schema::hasColumn('job_orders', 'am_change_made')) {
                $table->dropColumn('am_change_made');
            }
            if (Schema::hasColumn('job_orders', 'am_approval_status')) {
                $table->dropColumn('am_approval_status');
            }
        });
    }
};
