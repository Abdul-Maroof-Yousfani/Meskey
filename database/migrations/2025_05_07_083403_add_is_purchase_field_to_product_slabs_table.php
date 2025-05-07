<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->tinyInteger('is_purchase_field')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->dropColumn('is_purchase_field');
        });
    }
};
