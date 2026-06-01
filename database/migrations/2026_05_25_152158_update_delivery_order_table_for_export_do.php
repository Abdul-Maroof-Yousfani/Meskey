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
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->string('freight_amount')->nullable()->change();
            $table->longText('fumigation_by')->nullable();
            $table->longText('inspection_by')->nullable();
            $table->longText('phyto_certificate')->nullable();
            $table->string('carton_supplier')->nullable();
            $table->string('fumigation_tablets')->nullable();
            $table->string('fumigation_ref_no')->nullable();
        });

        Schema::table('export_delivery_order_packing_items', function (Blueprint $table) {
            $table->dropColumn([
                'fumigation_company_id',
                'carton_supplier',
                'fumigation_tablets',
                'fumigation_ref_no',
                'phyto_certificate',
                'inspection_company'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_delivery_order_packing_items', function (Blueprint $table) {
            $table->longText('fumigation_company_id')->nullable();
            $table->string('carton_supplier')->nullable();
            $table->string('fumigation_tablets')->nullable();
            $table->string('fumigation_ref_no')->nullable();
            $table->longText('phyto_certificate')->nullable();
            $table->longText('inspection_company')->nullable();
        });

        Schema::table('delivery_order', function (Blueprint $table) {
            $table->decimal('freight_amount', 10, 2)->nullable()->change();
            $table->dropColumn([
                'fumigation_by',
                'inspection_by',
                'phyto_certificate',
                'carton_supplier',
                'fumigation_tablets',
                'fumigation_ref_no'
            ]);
        });
    }
};
