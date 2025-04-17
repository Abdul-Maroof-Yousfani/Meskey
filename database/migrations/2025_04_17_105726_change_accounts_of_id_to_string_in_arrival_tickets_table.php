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
            $table->dropForeign(['accounts_of_id']);

            $table->dropColumn('accounts_of_id');
        });

        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->string('accounts_of_id')->nullable()->after('decision_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('accounts_of_id');
        });

        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('accounts_of_id')->nullable()->after('decision_id');

            $table->foreign('accounts_of_id')
                ->references('id')
                ->on('users');
        });
    }
};
