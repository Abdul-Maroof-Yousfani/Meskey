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
        Schema::table('loading_slips', function (Blueprint $table) {
            $table->string('labour')->nullable()->change();
            if (!Schema::hasColumn('loading_slips', 'empty_bags')) {
                $table->string('empty_bags')->nullable()->after('no_of_bags');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_slips', function (Blueprint $table) {
            $table->string('labour')->nullable(false)->change();
            if (Schema::hasColumn('loading_slips', 'empty_bags')) {
                $table->dropColumn('empty_bags');
            }
        });
    }
};
