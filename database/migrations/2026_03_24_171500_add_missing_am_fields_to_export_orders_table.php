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
        Schema::table('export_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('export_orders', 'packing_description')) {
                $table->text('packing_description')->nullable()->after('broker_id');
            }
            if (!Schema::hasColumn('export_orders', 'am_change_made')) {
                $table->boolean('am_change_made')->nullable()->default(1)->after('packing_description');
            }
            if (!Schema::hasColumn('export_orders', 'am_approval_status')) {
                $table->string('am_approval_status')->nullable()->default('pending')->after('am_change_made');
            }
            if (!Schema::hasColumn('export_orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('am_approval_status');

                // Check if the foreign key can be added safely
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            // $table->dropForeign(['created_by']);
            $table->dropColumn([
                'packing_description',
                'am_change_made',
                'am_approval_status',
                'created_by',
            ]);
        });
    }
};
