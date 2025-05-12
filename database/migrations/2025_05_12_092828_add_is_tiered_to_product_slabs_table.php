<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->tinyInteger('is_tiered')->default(1)->after('deduction_value');
        });
    }

    public function down(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->dropColumn('is_tiered');
        });
    }
};
