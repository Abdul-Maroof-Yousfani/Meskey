<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->json('company_location_ids')->nullable()->after('company_id');
            $table->json('arrival_location_ids')->nullable()->after('company_location_ids');
            $table->json('arrival_sub_location_ids')->nullable()->after('arrival_location_ids');
        });
    }

    public function down(): void
    {
        Schema::table('export_orders', function (Blueprint $table) {
            $table->dropColumn([
                'company_location_ids',
                'arrival_location_ids',
                'arrival_sub_location_ids',
            ]);
        });
    }
};
