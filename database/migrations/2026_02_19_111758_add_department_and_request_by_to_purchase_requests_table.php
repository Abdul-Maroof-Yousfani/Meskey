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
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('request_by_id')->nullable()->after('department_id');

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('request_by_id')->references('id')->on('request_bies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['request_by_id']);
            $table->dropColumn(['department_id', 'request_by_id']);
        });
    }
};
