<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('action');
            $table->integer('approval_cycle')->default(1)->after('status');
            $table->index(['record_id', 'module_id', 'approval_cycle']);
        });
    }

    public function down()
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropColumn(['status', 'approval_cycle']);
        });
    }
};
