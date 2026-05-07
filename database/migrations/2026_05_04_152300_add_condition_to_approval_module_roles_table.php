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
        Schema::table('approval_module_roles', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('approval_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_module_roles', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
