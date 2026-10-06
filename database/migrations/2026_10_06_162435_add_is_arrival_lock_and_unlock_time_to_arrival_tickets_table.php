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
            $table->boolean('is_arrival_lock')->nullable()->after('id');
            $table->timestamp('locked_at')->nullable()->after('is_arrival_lock');
            $table->timestamp('unlocked_at')->nullable()->after('locked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn(['is_arrival_lock', 'locked_at', 'unlocked_at']);
        });
    }
};