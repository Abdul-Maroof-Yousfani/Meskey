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
            $table->dropColumn('accounts_off_name');

            $table->unsignedBigInteger('accounts_of_id')->nullable()->after('decision_id');

            $table->foreign('accounts_of_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropForeign(['accounts_of_id']);
            $table->dropColumn('accounts_of_id');

            $table->string('accounts_off_name')->nullable();
        });
    }
};
