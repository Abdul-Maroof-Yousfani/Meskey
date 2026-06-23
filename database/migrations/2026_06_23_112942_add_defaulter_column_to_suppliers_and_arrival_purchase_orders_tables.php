<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('defaulter')->default(false)->after('status');
        });

        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->boolean('defaulter')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('defaulter');
        });

        Schema::table('arrival_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('defaulter');
        });
    }
};
