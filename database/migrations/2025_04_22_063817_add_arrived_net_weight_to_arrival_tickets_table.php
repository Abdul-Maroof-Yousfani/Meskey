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
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->string('arrived_net_weight')->nullable()->after('net_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('arrived_net_weight');
        });
    }
};
