<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_order', 'remarks')) {
                $table->text('remarks')->nullable()->after('empty_container_pickup');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_order', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_order', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
