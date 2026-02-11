<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('is_child_product', ['yes', 'no'])->default('no')->after('id');
            $table->enum('is_by_product', ['yes', 'no'])->default('no')->after('is_child_product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_child_product', 'is_by_product']);
        });
    }
};
