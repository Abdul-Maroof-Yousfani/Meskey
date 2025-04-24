<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->enum('line_type', ['bari', 'choti'])->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('line_type');
        });
    }
};
