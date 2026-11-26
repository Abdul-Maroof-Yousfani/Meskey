<?php

namespace App\Services\Export;

use App\Models\BagType;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrderPackingItem;
use App\Models\Product;
use App\Models\Production\JobOrder\JobOrder;
use Illuminate\Support\Collection;

/**
 * Packaging = one kind of bag of the export order's commodity (bag type + brand + size + condition + colour).
 *
 * The Job Order decides which packagings are produced, so a Delivery Order may only carry the
 * packagings of its Form-E's Job Order, each up to the Job Order quantity (no tolerance).
 *
 * Rows are matched by this key instead of ids because a Job Order update deletes and re-creates
 * its packing items (new ids), and older Delivery Orders were saved without any link.
 */
class ExportPackagingService
{
    /**
     * Same format is built in the Delivery Order create/edit JS (packagingKeyOfRow), keep both in sync.
     */
    public static function key($bagTypeId, $brandId, $bagSize, $bagConditionId, $bagColorId): string
    {
        return sprintf(
            '%d|%d|%.3f|%d|%d',
            (int) $bagTypeId,
            (int) $brandId,
            round((float) $bagSize, 3),
            (int) $bagConditionId,
            (int) $bagColorId
        );
    }

    /**
     * Bag type id of each Job Order packing item. The Job Order saves the bag as a Product
     * (bag_product_id, product_type general_items) while the Export / Delivery Order save a BagType;
     * both are the same bag by name (as JobOrderController maps them for sub items).
     *
     * @return array<int, int> job order packing item id => bag type id
     */
    public static function bagTypeIdsForJobOrderItems(Collection $items): array
    {
        $productNames = Product::whereIn('id', $items->pluck('bag_product_id')->filter()->unique())
            ->pluck('name', 'id');
        $bagTypeIdByName = BagType::all()
            ->mapWithKeys(fn($b) => [strtolower(trim($b->name)) => $b->id]);

        $result = [];
        foreach ($items as $item) {
            $name = $productNames[$item->bag_product_id] ?? null;
            $result[$item->id] = $name !== null
                ? (int) ($bagTypeIdByName[strtolower(trim($name))] ?? 0)
                // Not a product: treat the value as a bag type id
                : (int) $item->bag_product_id;
        }

        return $result;
    }

    /** Key of an Export Order or Delivery Order packing item (both use bag_type_id). */
    public static function keyOfPackingItem($item): string
    {
        return self::key($item->bag_type_id, $item->brand_id, $item->bag_size, $item->bag_condition_id, $item->bag_color_id);
    }

    /** Key of a submitted Delivery Order row (the form posts the bag type as bag_product_id). */
    public static function keyOfRequestRow(array $row): string
    {
        return self::key(
            $row['bag_product_id'] ?? null,
            $row['brand_id'] ?? null,
            $row['bag_size'] ?? 0,
            $row['bag_condition_id'] ?? null,
            $row['bag_color_id'] ?? null
        );
    }

    /**
     * Export Order packing item for a packaging: exact key, else same bag type + brand + size,
     * else same brand, else same size, else the first one.
     */
    public static function matchExportOrderItem(Collection $eoItems, $bagTypeId, $brandId, $bagSize, $bagConditionId, $bagColorId): ?ExportOrderPackingItem
    {
        if ($eoItems->isEmpty()) {
            return null;
        }

        $key = self::key($bagTypeId, $brandId, $bagSize, $bagConditionId, $bagColorId);
        $size = round((float) $bagSize, 3);

        return $eoItems->first(fn($p) => self::keyOfPackingItem($p) === $key)
            ?? $eoItems->first(fn($p) => (int) $p->bag_type_id === (int) $bagTypeId
                && (int) $p->brand_id === (int) $brandId
                && round((float) $p->bag_size, 3) === $size)
            ?? $eoItems->first(fn($p) => (int) $p->brand_id === (int) $brandId)
            ?? $eoItems->first(fn($p) => round((float) $p->bag_size, 3) === $size)
            ?? $eoItems->first();
    }

    /** Export Order packing item a Delivery Order packing row belongs to. */
    public static function exportOrderItemForDoRow($doRow): ?ExportOrderPackingItem
    {
        $eoItems = $doRow->deliveryOrder?->exportOrder?->packingItems ?? collect();

        return self::matchExportOrderItem(
            $eoItems,
            $doRow->bag_type_id,
            $doRow->brand_id,
            $doRow->bag_size,
            $doRow->bag_condition_id,
            $doRow->bag_color_id
        );
    }

    /**
     * Packagings of the Form-E's Job Order with allowed / used / remaining MT and the data the
     * Delivery Order form needs to build a row. Empty collection when the Form-E has no Job Order.
     *
     * @param int|null $exceptDoId Delivery Order being edited, its own rows are not counted as used
     */
    public static function packagingsForFormE(ExportFormE $formE, ?int $exceptDoId = null): Collection
    {
        if (!$formE->job_order_id) {
            return collect();
        }

        $jobOrder = JobOrder::with(['packingItems.brand', 'packingItems.subItems.bagType', 'packingItems.subItems.bagSize', 'exportOrder.packingItems'])
            ->find($formE->job_order_id);

        if (!$jobOrder || $jobOrder->packingItems->isEmpty()) {
            return collect();
        }

        $exportOrder = $jobOrder->exportOrder ?? $formE->exportOrder;
        $eoItems = $exportOrder?->packingItems ?? collect();

        // Used MT per packaging: rows of all non-cancelled DOs made on any Form-E of this Job Order
        $formEIds = ExportFormE::where('job_order_id', $jobOrder->id)->pluck('id');
        $usedByKey = ExportDeliveryOrder::with('exportPackingItems')
            ->whereIn('export_form_e_id', $formEIds)
            ->when($exceptDoId, fn($q) => $q->where('id', '!=', $exceptDoId))
            ->notCancelled()
            ->get()
            ->flatMap(fn($do) => $do->exportPackingItems)
            ->groupBy(fn($row) => self::keyOfPackingItem($row))
            ->map(fn($rows) => (float) $rows->sum('metric_tons'));

        $bagTypeIds = self::bagTypeIdsForJobOrderItems($jobOrder->packingItems);
        $bagTypeNames = BagType::whereIn('id', array_filter(array_unique(array_values($bagTypeIds))))
            ->pluck('name', 'id');

        // A Job Order can repeat the same packaging (e.g. per location): one DO row per packaging
        return $jobOrder->packingItems
            ->sortBy('id')
            ->groupBy(fn($item) => self::key($bagTypeIds[$item->id] ?? 0, $item->brand_id, $item->bag_size, $item->bag_condition_id, $item->bag_color_id))
            ->map(function ($items, $key) use ($usedByKey, $eoItems, $bagTypeNames, $bagTypeIds) {
                $first = $items->first();
                $bagTypeId = $bagTypeIds[$first->id] ?? 0;
                $jobOrderMt = (float) $items->sum('metric_tons');
                // A packaging cannot go above its Job Order quantity (no tolerance on the DO side)
                $allowedMt = $jobOrderMt;
                $usedMt = (float) ($usedByKey[$key] ?? 0);
                $remainingMt = max(0, $allowedMt - $usedMt);

                $eoItem = self::matchExportOrderItem(
                    $eoItems,
                    $bagTypeId,
                    $first->brand_id,
                    $first->bag_size,
                    $first->bag_condition_id,
                    $first->bag_color_id
                );

                $noOfBags = (int) $items->sum('no_of_bags');
                $extraPct = (float) ($first->extra_bags_percentage
                    ?: (($noOfBags > 0 && $items->sum('extra_bags') > 0) ? round(($items->sum('extra_bags') / $noOfBags) * 100, 2) : 0));
                $emptyPct = (float) ($first->empty_bags_percentage
                    ?: (($noOfBags > 0 && $items->sum('empty_bags') > 0) ? round(($items->sum('empty_bags') / $noOfBags) * 100, 2) : 0));

                $subItems = $first->subItems->sortBy('id')->map(function ($sub) {
                    $subNoOfBags = (int) $sub->no_of_bags;

                    return [
                        'bag_product_id' => $sub->bag_type_id,
                        'bag_type_id' => $sub->bag_type_id,
                        'bag_type_name' => $sub->bagType->name ?? '',
                        'bag_size_id' => $sub->bag_size_id,
                        'bag_size_name' => $sub->bagSize->name ?? ($sub->bagSize->size ?? ($sub->bag_size_id ?? '')),
                        'stitching_id' => $sub->stitching_id,
                        'bag_color_id' => $sub->bag_color_id,
                        'brand_id' => $sub->brand_id,
                        'thread_color_id' => $sub->thread_color_id,
                        'no_of_primary_bags' => $sub->no_of_primary_bags,
                        'no_of_bags' => 0,
                        'empty_bags' => 0,
                        'extra_bags' => 0,
                        'extra_bags_percentage' => (float) ($sub->extra_bags_percentage
                            ?: (($subNoOfBags > 0 && $sub->extra_bags > 0) ? round(($sub->extra_bags / $subNoOfBags) * 100, 2) : 0)),
                        'empty_bags_percentage' => (float) ($sub->empty_bags_percentage
                            ?: (($subNoOfBags > 0 && $sub->empty_bags > 0) ? round(($sub->empty_bags / $subNoOfBags) * 100, 2) : 0)),
                        'empty_bag_weight' => $sub->empty_bag_weight,
                        'total_bags' => 0,
                    ];
                })->values();

                return [
                    'packaging_key' => $key,
                    'job_order_mt' => round($jobOrderMt, 3),
                    'allowed_mt' => round($allowedMt, 3),
                    'used_mt' => round($usedMt, 3),
                    'remaining_mt' => round($remainingMt, 3),

                    // Row autofill (same shape as the Export Order autofill the DO form already reads)
                    'brand_id' => $first->brand_id,
                    'brand_name' => $first->brand->name ?? '',
                    // The DO form posts the bag type id as bag_product_id
                    'bag_product_id' => $bagTypeId,
                    'bag_type_id' => $bagTypeId,
                    'bag_type_name' => $bagTypeNames[$bagTypeId] ?? '',
                    'bag_condition_id' => $first->bag_condition_id,
                    'bag_color_id' => $first->bag_color_id,
                    'thread_color_id' => $first->thread_color_id,
                    'stitching_id' => $first->stitching_id,
                    'bag_size' => $first->bag_size,
                    'no_of_bags' => 0,
                    'extra_bags' => 0,
                    'extra_bags_percentage' => $extraPct,
                    'empty_bags' => 0,
                    'empty_bags_percentage' => $emptyPct,
                    'total_bags' => 0,
                    'total_kgs' => 0,
                    'metric_tons' => round($remainingMt, 3),
                    // Stuffing is always the Export Order's (Job Order value only when no EO row matches)
                    'stuffing_in_container' => $eoItem ? $eoItem->stuffing_in_container : $first->stuffing_in_container,
                    'no_of_containers' => $items->sum('no_of_containers'),
                    'min_weight_empty_bags' => $first->min_weight_empty_bags,
                    // Export Order row qty / amount: DO commission is worked out from these
                    'eo_metric_tons' => (float) ($eoItem->metric_tons ?? 0),
                    'eo_amount' => (float) ($eoItem->amount ?? 0),
                    'sub_items' => $subItems,
                ];
            })
            ->values();
    }

    /**
     * Stuffing (MT per container) of a submitted DO row: the matching Export Order row's value,
     * or the submitted value when the Export Order has no rows.
     */
    public static function exportOrderStuffingForRow(Collection $eoItems, array $row)
    {
        $eoItem = self::matchExportOrderItem(
            $eoItems,
            $row['bag_product_id'] ?? null,
            $row['brand_id'] ?? null,
            $row['bag_size'] ?? 0,
            $row['bag_condition_id'] ?? null,
            $row['bag_color_id'] ?? null
        );

        return $eoItem ? $eoItem->stuffing_in_container : ($row['stuffing_in_container'] ?? 0);
    }

    /**
     * Error message when the Delivery Order rows carry a packaging that is not in the Form-E's Job Order,
     * or more of a packaging than the Job Order has left. Null when everything fits.
     */
    public static function validateRows(ExportFormE $formE, array $rows, ?int $exceptDoId = null): ?string
    {
        // Older Form-Es without a Job Order: only the Form-E total check applies
        if (!$formE->job_order_id) {
            return null;
        }

        $packagings = self::packagingsForFormE($formE, $exceptDoId)->keyBy('packaging_key');
        if ($packagings->isEmpty()) {
            return null;
        }

        $requested = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row['bag_product_id'])) {
                continue;
            }
            $key = self::keyOfRequestRow($row);
            $requested[$key] = ($requested[$key] ?? 0) + (float) ($row['metric_tons'] ?? 0);
            $labels[$key] = $labels[$key] ?? self::label($row['bag_product_id'] ?? null, $row['bag_size'] ?? 0);
        }

        foreach ($requested as $key => $mt) {
            if (!$packagings->has($key)) {
                return "Packaging {$labels[$key]} is not in this Form-E's Job Order, so it cannot be added to this Delivery Order.";
            }

            $remaining = (float) $packagings[$key]['remaining_mt'];
            if ($mt > $remaining + 0.001) {
                return "Packaging {$labels[$key]}: Qty (" . round($mt, 3) . " MT) is more than the Job Order quantity left for it ("
                    . round($remaining, 3) . " MT of " . round((float) $packagings[$key]['job_order_mt'], 3) . " MT). Please correct the quantity.";
            }
        }

        return null;
    }

    private static function label($bagTypeId, $bagSize): string
    {
        $name = $bagTypeId ? (BagType::find($bagTypeId)->name ?? '') : '';

        return trim($name . ' ' . rtrim(rtrim(number_format((float) $bagSize, 2), '0'), '.') . 'kg');
    }

    /**
     * Delivery Challan ki ek row (ek truck + ek packaging) ke saath asal me kitne empty / extra bags gaye:
     * truck ke export loading slip se. (Bill of Lading + Commercial Invoice)
     *  - slip ki line items hon: isi DO packing row wali line ka count
     *  - purana slip (line items nahi): slip ka total, truck ki rows me qty ke hisaab se
     * Slip hi na mile to null (caller purana pro-rata hisaab lagata hai).
     *
     * @param  Collection  $ticketQty  ticket_id => us truck ki in challans me kul qty (MT)
     */
    public static function loadedBagCount($deliveryChallanData, string $field, Collection $ticketQty): ?float
    {
        $slip = $deliveryChallanData->loadingProgramItem?->exportLoadingSlip;
        if (!$slip) {
            return null;
        }

        $slipItems = $slip->items;
        if ($slipItems->isNotEmpty()) {
            $line = $slipItems->firstWhere('delivery_order_packing_item_id', $deliveryChallanData->do_data_id);
            return (float) ($line->{$field} ?? 0);
        }

        $total = (float) ($slip->{$field} ?? 0);
        $truckQty = (float) ($ticketQty[$deliveryChallanData->ticket_id] ?? 0);
        $rowQty = (float) ($deliveryChallanData->qty ?? 0);
        if ($total <= 0 || $truckQty <= 0) {
            return $total > 0 ? $total : 0.0;
        }

        return round($total * ($rowQty / $truckQty), 2);
    }
}
