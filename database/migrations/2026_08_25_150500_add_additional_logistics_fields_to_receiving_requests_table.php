<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->decimal('transporter_other_amount', 15, 2)->nullable()->default(0)->after('transporter_deduction');
            $table->decimal('demurrage_detention_amount', 15, 2)->nullable()->default(0)->after('transporter_other_amount');
            $table->unsignedBigInteger('sales_return_id')->nullable()->after('weighbridge_paid_by');
            $table->decimal('sales_return_qty', 15, 2)->nullable()->default(0)->after('sales_return_id');
            $table->decimal('sales_return_transporter_amount', 15, 2)->nullable()->default(0)->after('sales_return_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_requests', function (Blueprint $table) {
            $table->dropColumn([
                'transporter_other_amount',
                'demurrage_detention_amount',
                'sales_return_id',
                'sales_return_qty',
                'sales_return_transporter_amount'
            ]);
        });
    }
};
