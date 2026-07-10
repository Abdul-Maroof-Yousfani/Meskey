<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->string('other_attachment')->nullable()->after('bilty_return_attachment');
            $table->string('weightslip_attachment')->nullable()->after('other_attachment');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'other_attachment',
                'weightslip_attachment',
            ]);
        });
    }
};