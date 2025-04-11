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
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_purchase_order_id')->nullable()->after('accounts_of_id');
            $table->unsignedBigInteger('sauda_type_id')->nullable()->after('arrival_purchase_order_id');

            $table->foreign('arrival_purchase_order_id')
                ->references('id')
                ->on('arrival_purchase_orders');

            $table->foreign('sauda_type_id')
                ->references('id')
                ->on('sauda_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_tickets', function (Blueprint $table) {
            $table->dropForeign(['arrival_purchase_order_id']);
            $table->dropForeign(['sauda_type_id']);

            $table->dropColumn('arrival_purchase_order_id');
            $table->dropColumn('sauda_type_id');
        });
    }
};
