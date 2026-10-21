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
        if (Schema::hasTable('variables') && !Schema::hasColumn('variables', 'account_id')) {
            Schema::table('variables', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('company_id')->index();
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('variables') && Schema::hasColumn('variables', 'account_id')) {
            Schema::table('variables', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }
    }
};
