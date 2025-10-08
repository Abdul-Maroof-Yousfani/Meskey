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
        Schema::table('arrival_approves', function (Blueprint $table) {
            $table->unsignedBigInteger('gala_id')->nullable()->after('remark');
            $table->foreign('gala_id')->references('id')->on('arrival_sub_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_approves', function (Blueprint $table) {
            $table->dropForeign(['gala_id']);
            $table->dropColumn('gala_id');
        });
    }
};
