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
            $table->boolean('am_change_made')->nullable()->default(1)->after('packing_description');
            $table->string('am_approval_status')->nullable()->default('pending')->after('am_change_made');
            $table->unsignedBigInteger('created_by')->nullable()->after('am_approval_status');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'am_change_made',
                'am_approval_status',
                'created_by',
            ]);
        });
    }
};
