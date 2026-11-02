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
        // 1. Export Orders Table: close fields & attachment
        if (Schema::hasTable('export_orders')) {
            Schema::table('export_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('export_orders', 'close_order_reason')) {
                    $table->string('close_order_reason')->nullable();
                }

                if (!Schema::hasColumn('export_orders', 'closed_by')) {
                    $table->foreignId('closed_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('export_orders', 'closed_at')) {
                    $table->date('closed_at')->nullable();
                }

                if (!Schema::hasColumn('export_orders', 'attachment')) {
                    $table->string('attachment')->nullable()->after('marking_labeling');
                }
            });
        }

        // 2. Export Soda Fields Table: approval status & change made
        if (Schema::hasTable('export_soda_fields')) {
            Schema::table('export_soda_fields', function (Blueprint $table) {
                if (!Schema::hasColumn('export_soda_fields', 'am_approval_status')) {
                    $table->string('am_approval_status')->default('pending')->after('status');
                }

                if (!Schema::hasColumn('export_soda_fields', 'am_change_made')) {
                    $table->integer('am_change_made')->default(1)->after('am_approval_status');
                }
            });
        }

        // 3. Document Lists Table: port_id
        if (Schema::hasTable('document_lists') && !Schema::hasColumn('document_lists', 'port_id')) {
            Schema::table('document_lists', function (Blueprint $table) {
                $table->foreignId('port_id')
                    ->nullable()
                    ->after('feature')
                    ->constrained('ports')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Export Orders Table
        if (Schema::hasTable('export_orders')) {
            Schema::table('export_orders', function (Blueprint $table) {
                if (Schema::hasColumn('export_orders', 'closed_by')) {
                    $table->dropForeign(['closed_by']);
                    $table->dropColumn('closed_by');
                }
                if (Schema::hasColumn('export_orders', 'close_order_reason')) {
                    $table->dropColumn('close_order_reason');
                }
                if (Schema::hasColumn('export_orders', 'closed_at')) {
                    $table->dropColumn('closed_at');
                }
                if (Schema::hasColumn('export_orders', 'attachment')) {
                    $table->dropColumn('attachment');
                }
            });
        }

        // 2. Export Soda Fields Table
        if (Schema::hasTable('export_soda_fields')) {
            Schema::table('export_soda_fields', function (Blueprint $table) {
                if (Schema::hasColumn('export_soda_fields', 'am_approval_status')) {
                    $table->dropColumn('am_approval_status');
                }
                if (Schema::hasColumn('export_soda_fields', 'am_change_made')) {
                    $table->dropColumn('am_change_made');
                }
            });
        }

        // 3. Document Lists Table
        if (Schema::hasTable('document_lists') && Schema::hasColumn('document_lists', 'port_id')) {
            Schema::table('document_lists', function (Blueprint $table) {
                $table->dropForeign(['port_id']);
                $table->dropColumn('port_id');
            });
        }
    }
};

