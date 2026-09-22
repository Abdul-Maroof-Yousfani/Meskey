<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grn_numbers', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('product_id')
                ->constrained('arrival_purchase_orders')
                ->nullOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('suppliers')
                ->nullOnDelete();
        });

        DB::statement("
            UPDATE grn_numbers gn
            INNER JOIN arrival_slips aslip ON gn.model_id = aslip.id
            INNER JOIN arrival_tickets aticket ON aslip.arrival_ticket_id = aticket.id
            INNER JOIN arrival_purchase_orders apo ON aticket.arrival_purchase_order_id = apo.id
            SET gn.purchase_order_id = apo.id,
                gn.supplier_id = apo.supplier_id
            WHERE gn.model_type = 'arrival-slip'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grn_numbers', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['purchase_order_id', 'supplier_id']);
        });
    }
};
