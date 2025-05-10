<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('status');
        });
    }

    public function down()
    {
        Schema::table('product_slabs', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
