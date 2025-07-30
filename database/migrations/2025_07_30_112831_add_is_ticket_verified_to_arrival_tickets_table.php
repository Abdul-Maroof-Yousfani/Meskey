<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->tinyInteger('is_ticket_verified')->default(0)->after('bilty_return_attachment');
            $table->unsignedBigInteger('ticket_verified_by')->nullable()->after('is_ticket_verified');

            $table->foreign('ticket_verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropForeign(['ticket_verified_by']);
            $table->dropColumn(['is_ticket_verified', 'ticket_verified_by']);
        });
    }
};
