<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_modules', function (Blueprint $table) {
            $table->string('approval_column')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('approval_modules', function (Blueprint $table) {
            $table->dropColumn('approval_column');
        });
    }
};
