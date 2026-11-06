<?php

namespace App\Services\Production\Milling;

use App\Http\Controllers\Production\ProductionVoucherController;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionVoucherMachineTime;

/**
 * Each Machine Plan Setting gets its own Production Voucher, created as a draft.
 * The draft carries the basics (date, location, plant, job orders of the machine cards);
 * the user then edits the voucher itself to complete it. Once saved there it is no
 * longer a draft and the plan stops overwriting it.
 */
class MachinePlanDraftVoucherService
{
    public function syncForPlan(MachinePlanSetting $plan): ?ProductionVoucher
    {
        $plan->loadMissing(['plant', 'items']);
        $plant = $plan->plant;

        $locationId = $plan->company_location_id ?: optional($plant)->company_location_id;
        if (!$locationId) {
            // A voucher needs a location; nothing to create without one
            return null;
        }

        $jobOrderIds = $plan->items
            ->flatMap(fn ($item) => $item->job_order_id_list)
            ->unique()
            ->values()
            ->all();

        $voucher = $plan->production_voucher_id ? ProductionVoucher::find($plan->production_voucher_id) : null;

        if ($voucher && !$voucher->is_draft) {
            // Already completed from the Production Voucher screen: leave it as it is
            return $voucher;
        }

        $basics = [
            'prod_date' => $plan->date,
            'location_id' => $locationId,
            'sub_location_id' => optional($plant)->arrival_location_id ?: 0,
            'plant_id' => $plan->plant_id,
            'job_order_id' => $jobOrderIds[0] ?? null,
        ];

        if (!$voucher) {
            // auth()->id() returns the username in this app, so take the numeric id from the user model
            $userId = auth()->user()?->id ?? $plan->user_id;

            $voucher = ProductionVoucher::create($basics + [
                'company_id' => $plan->company_id,
                'prod_no' => ProductionVoucherController::generateUniqueNumber($plan->date, $locationId),
                'product_id' => null, // chosen when the voucher is completed
                'user_id' => $userId,
                'supervisor_id' => $userId,
                'status' => 'draft',
                'is_draft' => true,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
                'remarks' => $plan->remarks,
            ]);

            $plan->update(['production_voucher_id' => $voucher->id]);
        } else {
            $voucher->update($basics);
        }

        $voucher->jobOrders()->sync($jobOrderIds);

        // Machine rows and the day's plant breakdown belong to this voucher too
        ProductionVoucherMachineTime::where('machine_plan_setting_id', $plan->id)
            ->update(['production_voucher_id' => $voucher->id]);

        PlantBreakdown::where('plant_id', $plan->plant_id)
            ->whereDate('date', $plan->date)
            ->whereNull('production_voucher_id')
            ->update(['production_voucher_id' => $voucher->id]);

        return $voucher;
    }

    /** Remove the plan's voucher when the plan is deleted, but only while it is still an untouched draft. */
    public function deleteDraftForPlan(MachinePlanSetting $plan): void
    {
        if (!$plan->production_voucher_id) {
            return;
        }

        $voucher = ProductionVoucher::find($plan->production_voucher_id);
        if ($voucher && $voucher->is_draft && !$voucher->inputs()->exists() && !$voucher->outputs()->exists()) {
            $voucher->jobOrders()->detach();
            $voucher->delete();
        }
    }
}
