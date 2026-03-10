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
        Schema::table('logistics_items', function (Blueprint $table) {
            $table->renameColumn('transporter', 'transporter_name');
            $table->unsignedBigInteger('transporter_id')->nullable()->after('logistics_id');
            
            $table->foreign('transporter_id')->references('id')->on('transporters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_items', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn('transporter_id');
            $table->renameColumn('transporter_name', 'transporter');
        });
    }
};
