<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_of_ladings', 'selected_form_e_ids')) {
                $table->json('selected_form_e_ids')->nullable()->after('export_order_id');
            }

            if (!Schema::hasColumn('bill_of_ladings', 'selected_delivery_challan_ids')) {
                $table->json('selected_delivery_challan_ids')->nullable()->after('selected_form_e_ids');
            }

            if (!Schema::hasColumn('bill_of_ladings', 'selected_delivery_order_ids')) {
                $table->json('selected_delivery_order_ids')->nullable()->after('selected_delivery_challan_ids');
            }

            // NOTE: charter_party_dated / cautions_text are part of base table schema.
        });

        DB::table('bill_of_ladings')->orderBy('id')->get()->each(function ($row) {
            $formEIds = [];
            $deliveryOrderIds = [];
            $deliveryChallanIds = [];

            if (!empty($row->export_delivery_challan_id)) {
                $deliveryChallanIds[] = (int) $row->export_delivery_challan_id;
            }

            if (!empty($row->delivery_order_id)) {
                $deliveryOrderIds[] = (int) $row->delivery_order_id;

                $formEId = DB::table('delivery_order')
                    ->where('id', $row->delivery_order_id)
                    ->value('export_form_e_id');

                if ($formEId) {
                    $formEIds[] = (int) $formEId;
                }
            }

            DB::table('bill_of_ladings')
                ->where('id', $row->id)
                ->update([
                    'selected_form_e_ids' => !empty($formEIds) ? json_encode(array_values(array_unique($formEIds))) : null,
                    'selected_delivery_challan_ids' => !empty($deliveryChallanIds) ? json_encode(array_values(array_unique($deliveryChallanIds))) : null,
                    'selected_delivery_order_ids' => !empty($deliveryOrderIds) ? json_encode(array_values(array_unique($deliveryOrderIds))) : null,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            if (Schema::hasColumn('bill_of_ladings', 'selected_form_e_ids')) {
                $table->dropColumn('selected_form_e_ids');
            }

            if (Schema::hasColumn('bill_of_ladings', 'selected_delivery_challan_ids')) {
                $table->dropColumn('selected_delivery_challan_ids');
            }

            if (Schema::hasColumn('bill_of_ladings', 'selected_delivery_order_ids')) {
                $table->dropColumn('selected_delivery_order_ids');
            }

            // NOTE: keep base columns untouched in down.

            // No index changes in down; preserve existing DB constraints.
        });
    }
};
