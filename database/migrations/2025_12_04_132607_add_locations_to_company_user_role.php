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
        Schema::table('company_user_role', function (Blueprint $table) {
            $table->json('locations')->nullable()->after('role_id');
            $table->json('arrival_locations')->nullable()->after('locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_user_role', function (Blueprint $table) {
            $table->dropColumn(['locations', 'arrival_locations']);
        });
    }
};
