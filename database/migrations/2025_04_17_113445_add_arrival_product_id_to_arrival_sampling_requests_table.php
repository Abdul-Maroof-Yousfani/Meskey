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
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_product_id')->nullable()->after('arrival_ticket_id');

            $table->foreign('arrival_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_sampling_requests', function (Blueprint $table) {
            $table->dropForeign(['arrival_product_id']);

            $table->dropColumn('arrival_product_id');
        });
    }
};
