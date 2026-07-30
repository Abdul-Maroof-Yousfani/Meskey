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
        // Add columns
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_ticket_id')->nullable()->after('id');
            $table->unsignedBigInteger('purchase_ticket_id')->nullable()->after('arrival_ticket_id');
            $table->string('grn_no')->nullable()->after('purchase_ticket_id');
        });

        // Add foreign keys (separate to avoid issues if tables don't exist yet)
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->foreign('arrival_ticket_id')
                ->references('id')
                ->on('arrival_tickets')
                ->onDelete('set null');

            $table->foreign('purchase_ticket_id')
                ->references('id')
                ->on('purchase_tickets')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_request_datas', function (Blueprint $table) {
            $table->dropForeign(['arrival_ticket_id']);
            $table->dropForeign(['purchase_ticket_id']);
            $table->dropColumn(['arrival_ticket_id', 'purchase_ticket_id', 'grn_no']);
        });
    }
};