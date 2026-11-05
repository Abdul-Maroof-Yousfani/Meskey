<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A voucher auto-created from a Machine Plan Setting stays a draft
        // until someone edits and saves it from the Production Voucher screen.
        Schema::table('production_vouchers', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('production_vouchers', function (Blueprint $table) {
            $table->dropColumn('is_draft');
        });
    }
};
