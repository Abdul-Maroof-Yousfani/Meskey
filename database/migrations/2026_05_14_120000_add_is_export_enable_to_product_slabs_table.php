<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->boolean('is_export_enable')->default(false)->after('prefill_spec_value');
        });
    }

    public function down(): void
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->dropColumn('is_export_enable');
        });
    }
};
