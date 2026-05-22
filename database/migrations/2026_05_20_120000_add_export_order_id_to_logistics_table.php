<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('logistics', function (Blueprint $table) {
            if (!Schema::hasColumn('logistics', 'type')) {
                $table->string('type')->nullable()->after('date');
            }

            if (Schema::hasColumn('logistics', 'gala') && !Schema::hasColumn('logistics', 'section')) {
                $table->renameColumn('gala', 'section');
            }

            if (!Schema::hasColumn('logistics', 'factory')) {
                $table->string('factory')->nullable()->after('location');
            }

            if (!Schema::hasColumn('logistics', 'section')) {
                $table->string('section')->nullable()->after('factory');
            }

            if (!Schema::hasColumn('logistics', 'to_location')) {
                $table->string('to_location')->nullable()->after('location');
            }

            if (!Schema::hasColumn('logistics', 'am_approval_status')) {
                $table->string('am_approval_status')->nullable()->default('pending');
            }

            if (!Schema::hasColumn('logistics', 'am_change_made')) {
                $table->tinyInteger('am_change_made')->nullable()->default(1);
            }

            if (!Schema::hasColumn('logistics', 'export_order_id')) {
                $table->foreignId('export_order_id')->nullable()->after('sale_order_id')->constrained('export_orders')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics', function (Blueprint $table) {
            if (Schema::hasColumn('logistics', 'export_order_id')) {
                $table->dropForeign(['export_order_id']);
                $table->dropColumn('export_order_id');
            }

            if (Schema::hasColumn('logistics', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('logistics', 'to_location')) {
                $table->dropColumn('to_location');
            }

            if (Schema::hasColumn('logistics', 'am_approval_status')) {
                $table->dropColumn('am_approval_status');
            }

            if (Schema::hasColumn('logistics', 'am_change_made')) {
                $table->dropColumn('am_change_made');
            }

            if (Schema::hasColumn('logistics', 'section') && !Schema::hasColumn('logistics', 'gala')) {
                $table->renameColumn('section', 'gala');
            }

            if (Schema::hasColumn('logistics', 'factory')) {
                $table->dropColumn('factory');
            }
        });
    }
};
