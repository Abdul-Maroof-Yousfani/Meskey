<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_sampling_results', function (Blueprint $table) {
            $table->decimal('relief_deduction', 6, 2)->nullable()->after('applied_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_sampling_results', function (Blueprint $table) {
            $table->dropColumn('relief_deduction');
        });
    }
};
