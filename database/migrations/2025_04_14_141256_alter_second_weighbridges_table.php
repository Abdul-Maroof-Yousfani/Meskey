<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('second_weighbridges', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('arrival_ticket_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('arrival_ticket_id');
            $table->string('remark')->nullable()->after('created_by');
            $table->string('weight')->nullable()->after('remark');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('arrival_ticket_id')->references('id')->on('arrival_tickets')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            if (!Schema::hasColumn('second_weighbridges', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('second_weighbridges', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['arrival_ticket_id']);
            $table->dropForeign(['created_by']);

            $table->dropColumn('company_id');
            $table->dropColumn('arrival_ticket_id');
            $table->dropColumn('created_by');
            $table->dropColumn('remark');
            $table->dropColumn('weight');

            if (Schema::hasColumn('second_weighbridges', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
