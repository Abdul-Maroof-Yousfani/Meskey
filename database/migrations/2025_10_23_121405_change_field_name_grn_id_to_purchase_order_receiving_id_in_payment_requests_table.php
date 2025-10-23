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
        Schema::table('payment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('payment_requests', 'grn_id')) {
                $table->dropForeign(['grn_id']);
                $table->dropColumn('grn_id');
            }

            $table->foreignId('purchase_order_receiving_id')->nullable()->after('purchase_order_id')->constrained('purchase_order_receivings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_receiving_id']);
            $table->dropColumn('purchase_order_receiving_id');

            $table->foreignId('grn_id')->nullable()->after('purchase_order_id')->constrained('good_receive_notes');
        });
    }
};
