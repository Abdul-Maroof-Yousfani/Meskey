<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('export_soda_fields', function (Blueprint $table) {
            // Add new commission fields
            $table->decimal('commission_percentage', 15, 2)->nullable()->after('shipment_period');
            $table->decimal('commission_amount_per_ton', 15, 2)->nullable()->after('commission_percentage');

            // Drop unused columns and their foreign keys
            $table->dropForeign(['bag_packing_id']);
            $table->dropColumn([
                'bag_packing_id',
                'price_per_kg',
                'price_per_mound',
                'price_per_100_kg',
                'quantity_in_kg',
                'quantity_in_ton'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('export_soda_fields', function (Blueprint $table) {
            $table->unsignedBigInteger('bag_packing_id')->nullable();
            $table->decimal('price_per_kg', 15, 4)->nullable();
            $table->decimal('price_per_mound', 15, 4)->nullable();
            $table->decimal('price_per_100_kg', 15, 4)->nullable();
            $table->decimal('quantity_in_kg', 15, 4)->nullable();
            $table->decimal('quantity_in_ton', 15, 4)->nullable();

            $table->foreign('bag_packing_id')->references('id')->on('bag_packings');

            $table->dropColumn(['commission_percentage', 'commission_amount_per_ton']);
        });
    }
};
