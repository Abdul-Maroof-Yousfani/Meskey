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
        if (Schema::hasTable('production_analysis')) {
            Schema::table('production_analysis', function (Blueprint $table) {
                if (!Schema::hasColumn('production_analysis', 'production_voucher_id')) {
                    $table->unsignedBigInteger('production_voucher_id')->nullable()->after('analysis_request_id');
                    $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('production_machine_analysis')) {
            Schema::table('production_machine_analysis', function (Blueprint $table) {
                if (!Schema::hasColumn('production_machine_analysis', 'production_voucher_id')) {
                    $table->unsignedBigInteger('production_voucher_id')->nullable()->after('analysis_request_id');
                    $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->nullOnDelete();
                }
                if (!Schema::hasColumn('production_machine_analysis', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->after('analysis_request_id');
                    $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('production_analysis_requests')) {
            Schema::table('production_analysis_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('production_analysis_requests', 'production_voucher_id')) {
                    $table->unsignedBigInteger('production_voucher_id')->nullable()->after('job_order_id');
                    $table->foreign('production_voucher_id')->references('id')->on('production_vouchers')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('production_analysis')) {
            Schema::table('production_analysis', function (Blueprint $table) {
                if (Schema::hasColumn('production_analysis', 'production_voucher_id')) {
                    $table->dropForeign(['production_voucher_id']);
                    $table->dropColumn('production_voucher_id');
                }
            });
        }

        if (Schema::hasTable('production_machine_analysis')) {
            Schema::table('production_machine_analysis', function (Blueprint $table) {
                if (Schema::hasColumn('production_machine_analysis', 'production_voucher_id')) {
                    $table->dropForeign(['production_voucher_id']);
                    $table->dropColumn('production_voucher_id');
                }
                if (Schema::hasColumn('production_machine_analysis', 'product_id')) {
                    $table->dropForeign(['product_id']);
                    $table->dropColumn('product_id');
                }
            });
        }

        if (Schema::hasTable('production_analysis_requests')) {
            Schema::table('production_analysis_requests', function (Blueprint $table) {
                if (Schema::hasColumn('production_analysis_requests', 'production_voucher_id')) {
                    $table->dropForeign(['production_voucher_id']);
                    $table->dropColumn('production_voucher_id');
                }
            });
        }
    }
};
