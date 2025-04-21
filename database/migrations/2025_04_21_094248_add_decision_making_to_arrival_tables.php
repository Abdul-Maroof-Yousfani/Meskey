<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->tinyInteger('decision_making')->after('sauda_type_id')->default(0);
        });

        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->tinyInteger('decision_making')->after('sample_taken_by')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropColumn('decision_making');
        });

        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropColumn('decision_making');
        });
    }
};
