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
        // 1. Update delivery_order table
        Schema::table('delivery_order', function (Blueprint $table) {
            // Add fields if they don't exist, ensure they are nullable
            if (!Schema::hasColumn('delivery_order', 'financial_instrument_no')) {
                $table->string('financial_instrument_no')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('delivery_order', 'job_order_no')) {
                $table->string('job_order_no')->nullable()->after('financial_instrument_no');
            }
            if (!Schema::hasColumn('delivery_order', 'vessel_name')) {
                $table->string('vessel_name')->nullable()->after('job_order_no');
            }
            if (!Schema::hasColumn('delivery_order', 'vessel_etd')) {
                $table->date('vessel_etd')->nullable()->after('vessel_name');
            }
            if (!Schema::hasColumn('delivery_order', 'vessel_eta')) {
                $table->date('vessel_eta')->nullable()->after('vessel_etd');
            }
            if (!Schema::hasColumn('delivery_order', 'loading_date')) {
                $table->date('loading_date')->nullable()->after('vessel_eta');
            }
            if (!Schema::hasColumn('delivery_order', 'estimated_payment_date')) {
                $table->date('estimated_payment_date')->nullable()->after('loading_date');
            }
            if (!Schema::hasColumn('delivery_order', 'freight_amount')) {
                $table->decimal('freight_amount', 15, 2)->default(0)->nullable()->after('estimated_payment_date');
            }
            if (!Schema::hasColumn('delivery_order', 'transporter_id')) {
                $table->unsignedBigInteger('transporter_id')->nullable()->after('freight_amount');
            }
            if (!Schema::hasColumn('delivery_order', 'c_agent')) {
                $table->string('c_agent')->nullable()->after('transporter_id');
            }
            if (!Schema::hasColumn('delivery_order', 'shipping_line')) {
                $table->string('shipping_line')->nullable()->after('c_agent');
            }
            if (!Schema::hasColumn('delivery_order', 'empty_container_pickup')) {
                $table->string('empty_container_pickup')->nullable()->after('shipping_line');
            }
            if (!Schema::hasColumn('delivery_order', 'remarks')) {
                $table->text('remarks')->nullable()->after('empty_container_pickup');
            }
        });

        // 2. Update export_delivery_order_packing_items table
        Schema::table('export_delivery_order_packing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_delivery_order_packing_items', 'carton_supplier')) {
                $table->string('carton_supplier')->nullable()->after('fumigation_company_id');
            }
            if (!Schema::hasColumn('export_delivery_order_packing_items', 'fumigation_tablets')) {
                $table->string('fumigation_tablets')->nullable()->after('carton_supplier');
            }
            if (!Schema::hasColumn('export_delivery_order_packing_items', 'fumigation_ref_no')) {
                $table->string('fumigation_ref_no')->nullable()->after('fumigation_tablets');
            }
            if (!Schema::hasColumn('export_delivery_order_packing_items', 'phyto_certificate')) {
                $table->json('phyto_certificate')->nullable()->after('fumigation_ref_no');
            }
            if (!Schema::hasColumn('export_delivery_order_packing_items', 'inspection_company')) {
                $table->text('inspection_company')->nullable()->after('phyto_certificate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_delivery_order_packing_items', function (Blueprint $table) {
            $table->dropColumn([
                'carton_supplier',
                'fumigation_tablets',
                'fumigation_ref_no',
                'phyto_certificate',
                'inspection_company'
            ]);
        });

        Schema::table('delivery_order', function (Blueprint $table) {
            $table->string('inspection_company')->nullable();
            $table->string('carton_supplier')->nullable();
            $table->string('fumigation_tablets')->nullable();
            $table->string('fumigation_ref_no')->nullable();
            $table->string('fumigation')->nullable();
            $table->string('phyto_certificate')->nullable();

            $table->dropColumn([
                'financial_instrument_no',
                'job_order_no',
                'vessel_name',
                'vessel_etd',
                'vessel_eta',
                'loading_date',
                'estimated_payment_date',
                'freight_amount',
                'transporter_id',
                'c_agent',
                'shipping_line',
                'empty_container_pickup',
                'remarks'
            ]);
        });
    }
};
