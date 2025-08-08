<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_freights', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_ticket_id')->nullable()->after('purchase_ticket_id');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_freights', function (Blueprint $table) {
            $table->dropForeign(['arrival_ticket_id']);
            $table->dropColumn('arrival_ticket_id');
        });
    }
};
