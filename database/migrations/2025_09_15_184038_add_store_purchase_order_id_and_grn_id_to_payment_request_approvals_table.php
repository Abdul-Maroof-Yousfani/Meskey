<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('store_purchase_order_id')->nullable()->after('purchase_order_id');
            $table->unsignedBigInteger('grn_id')->nullable()->after('store_purchase_order_id');

            $table->foreign('store_purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('set null');

            $table->foreign('grn_id')
                ->references('id')
                ->on('good_receive_notes')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->dropForeign(['store_purchase_order_id']);
            $table->dropForeign(['grn_id']);
            $table->dropColumn(['store_purchase_order_id', 'grn_id']);
        });
    }
};
