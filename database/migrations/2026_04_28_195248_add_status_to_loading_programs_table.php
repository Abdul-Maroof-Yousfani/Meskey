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
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->string('vessel_name')->nullable()->after('sub_arrival_locations');
            $table->string('status')->nullable()->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_programs', function (Blueprint $table) {
            $table->dropColumn(['status', 'vessel_name']);
        });
    }
};
