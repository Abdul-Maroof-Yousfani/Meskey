<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('am_approval_status')->default('pending');
            $table->integer('am_change_made')->default('1');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
            $table->dropColumn('am_approval_status');
            $table->dropColumn('am_change_made');
        });
    }
};
