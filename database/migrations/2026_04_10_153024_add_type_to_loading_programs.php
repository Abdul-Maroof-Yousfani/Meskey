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
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->string('type')->nullable()->after('id');
        });

        // Initialize existing data
        \DB::table('loading_programs')
            ->whereNotNull('export_order_id')
            ->update(['type' => 'export_order']);

        \DB::table('loading_programs')
            ->whereNull('type')
            ->update(['type' => 'sale_order']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
