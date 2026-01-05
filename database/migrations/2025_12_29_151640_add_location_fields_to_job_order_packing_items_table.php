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
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('no_of_containers');
            $table->text('location_instruction')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_packing_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'location_instruction']);
        });
    }
};
