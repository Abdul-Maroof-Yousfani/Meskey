<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('table_name')->nullable()->after('name');
            $table->unsignedBigInteger('request_account_id')->after('table_name');
            $table->unsignedBigInteger('model_id')->nullable()->after('request_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('table_name');
            $table->dropColumn('request_account_id');
            $table->dropColumn('model_id');
        });
    }
};
