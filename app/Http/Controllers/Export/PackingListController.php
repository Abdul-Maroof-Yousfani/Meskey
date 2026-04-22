<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\CommercialInvoiceController;
use App\Models\Export\CommercialInvoice;
use App\Models\Export\ExportOrder;
use App\Models\Export\PackingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackingListController extends CommercialInvoiceController
{
    public function index(): View
    {
        return view('management.export.packing-list.index');
    }

    public function getPackingListTable(Request $request): View
    {
        $search = trim((string) $request->get('search'));

        $packingLists = PackingList::with(['commercialInvoice.exportOrder', 'billOfLading'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('commercialInvoice', function ($subQ) use ($search) {
                        $subQ->where('commercial_invoice_no', 'like', "%{$search}%")
                            ->orWhere('invoice_no', 'like', "%{$search}%")
                            ->orWhereHas('exportOrder', function ($eoQ) use ($search) {
                                $eoQ->where('voucher_no', 'like', "%{$search}%")
                                    ->orWhere('contract_no', 'like', "%{$search}%");
                            });
                    })->orWhereHas('billOfLading', function ($subQ) use ($search) {
                        $subQ->where('bill_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        $packingLists->getCollection()->transform(function (PackingList $packingList) {
            [, $preview] = $this->resolvePreviewForPackingList($packingList);
            $packingList->computed_preview = $preview;

            return $packingList;
        });

        return view('management.export.packing-list.getList', compact('packingLists'));
    }

    public function create(): View
    {
        return view('management.export.packing-list.create', [
            'packingList' => null,
            'exportOrders' => $this->getEligibleExportOrdersForPackingList(),
            'preview' => null,
            'goodsSummary' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePackingList($request);

        DB::beginTransaction();

        try {
            [$commercialInvoice, $preview, $goodsSummary] = $this->buildPayloadFromCommercialInvoiceId((int) $validated['commercial_invoice_id']);

            PackingList::create([
                'export_order_id' => $commercialInvoice->export_order_id,
                'commercial_invoice_id' => $commercialInvoice->id,
                'bill_of_lading_id' => $commercialInvoice->bill_of_lading_id,
                'snapshot_data' => $preview,
                'goods_summary' => $goodsSummary,
                'created_by' => auth()->user()?->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Packing List has been created']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id): View
    {
        $packingList = PackingList::with(['commercialInvoice', 'billOfLading'])->findOrFail($id);
        [, $preview, $goodsSummary] = $this->resolvePreviewForPackingList($packingList);

        return view('management.export.packing-list.show', [
            'packingList' => $packingList,
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function edit($id): View
    {
        $packingList = PackingList::with(['commercialInvoice'])->findOrFail($id);
        [, $preview, $goodsSummary] = $this->resolvePreviewForPackingList($packingList);

        return view('management.export.packing-list.edit', [
            'packingList' => $packingList,
            'exportOrders' => $this->getEligibleExportOrdersForPackingList($packingList->id),
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $packingList = PackingList::findOrFail($id);
        $validated = $this->validatePackingList($request, $packingList->id);

        DB::beginTransaction();

        try {
            [$commercialInvoice, $preview, $goodsSummary] = $this->buildPayloadFromCommercialInvoiceId((int) $validated['commercial_invoice_id']);

            $packingList->update([
                'export_order_id' => $commercialInvoice->export_order_id,
                'commercial_invoice_id' => $commercialInvoice->id,
                'bill_of_lading_id' => $commercialInvoice->bill_of_lading_id,
                'snapshot_data' => $preview,
                'goods_summary' => $goodsSummary,
            ]);

            DB::commit();

            return response()->json(['message' => 'Packing List has been updated']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $packingList = PackingList::findOrFail($id);
        $packingList->delete();

        return response()->json(['message' => 'Packing List has been deleted']);
    }

    public function getRelatedData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commercial_invoice_id' => [
                'required',
                'integer',
                'exists:commercial_invoices,id',
            ],
        ]);

        [, $preview, $goodsSummary] = $this->buildPayloadFromCommercialInvoiceId((int) $validated['commercial_invoice_id']);

        return response()->json([
            'success' => true,
            'preview_html' => view('management.export.packing-list.preview', [
                'preview' => $preview,
                'goodsSummary' => $goodsSummary,
            ])->render(),
            'preview' => $preview,
            'goods_summary' => $goodsSummary,
        ]);
    }

    public function getCommercialInvoicesByExportOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_order_id' => ['required', 'integer', 'exists:export_orders,id'],
            'current_packing_list_id' => ['nullable', 'integer', 'exists:packing_lists,id'],
        ]);

        $invoices = $this->getEligibleCommercialInvoicesByExportOrder(
            (int) $validated['export_order_id'],
            isset($validated['current_packing_list_id']) ? (int) $validated['current_packing_list_id'] : null
        )->map(function (CommercialInvoice $commercialInvoice) {
            return [
                'id' => $commercialInvoice->id,
                'text' => trim(
                    ($commercialInvoice->invoice_no ?: $commercialInvoice->commercial_invoice_no ?: 'N/A')
                    . ' - ' .
                    ($commercialInvoice->billOfLading->bill_no ?? 'N/A')
                ),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    protected function validatePackingList(Request $request, ?int $packingListId = null): array
    {
        return $request->validate([
            'commercial_invoice_id' => [
                'required',
                'integer',
                'exists:commercial_invoices,id',
                Rule::unique('packing_lists', 'commercial_invoice_id')->ignore($packingListId),
            ],
        ]);
    }

    protected function getEligibleCommercialInvoicesByExportOrder(int $exportOrderId, ?int $currentPackingListId = null)
    {
        $takenInvoiceIds = PackingList::query()
            ->when($currentPackingListId, fn($q) => $q->where('id', '!=', $currentPackingListId))
            ->pluck('commercial_invoice_id')
            ->filter()
            ->values()
            ->all();

        return CommercialInvoice::with(['exportOrder', 'billOfLading'])
            ->where('export_order_id', $exportOrderId)
            ->whereNotIn('id', $takenInvoiceIds)
            ->latest()
            ->get();
    }

    protected function getEligibleExportOrdersForPackingList(?int $currentPackingListId = null)
    {
        $takenInvoiceIds = PackingList::query()
            ->when($currentPackingListId, fn($q) => $q->where('id', '!=', $currentPackingListId))
            ->pluck('commercial_invoice_id')
            ->filter()
            ->values()
            ->all();

        return ExportOrder::with(['buyer'])
            ->whereHas('commercialInvoices', function ($query) use ($takenInvoiceIds) {
                $query->when(!empty($takenInvoiceIds), function ($subQuery) use ($takenInvoiceIds) {
                    $subQuery->whereNotIn('id', $takenInvoiceIds);
                });
            })
            ->latest()
            ->get();
    }

    protected function resolvePreviewForPackingList(PackingList $packingList): array
    {
        if (!empty($packingList->snapshot_data) && !empty($packingList->goods_summary)) {
            return [$packingList->commercialInvoice, $packingList->snapshot_data, $packingList->goods_summary];
        }

        return $this->buildPayloadFromCommercialInvoiceId((int) $packingList->commercial_invoice_id);
    }

    protected function buildPayloadFromCommercialInvoiceId(int $commercialInvoiceId): array
    {
        $commercialInvoice = CommercialInvoice::with(['exportOrder', 'billOfLading'])->findOrFail($commercialInvoiceId);
        [$billOfLadings, $ciPreview, $ciGoodsSummary] = $this->buildPayloadFromInvoice($commercialInvoice);

        $commercialInvoice->loadMissing([
            'exportOrder.company',
            'exportOrder.product',
            'exportOrder.portOfLoading.country',
            'exportOrder.portOfDischarge.country',
            'exportOrder.originCountry',
            'exportOrder.packingItems.brand',
            'exportOrder.packingItems.bagCondition',
            'exportOrder.packingItems.bagType',
            'exportOrder.packingItems.subItems',
        ]);

        $packingMetaRows = $this->buildPackingMetaRowsFromExportOrder($commercialInvoice->exportOrder);
        $packingMetaByKey = collect($packingMetaRows)
            ->keyBy(fn($row) => $row['key'])
            ->all();

        // $rows = collect($ciGoodsSummary['rows'] ?? [])->values()->map(function ($row, $index) use ($packingMetaByKey) {

        //     $key = $this->makePackingMetaKeyFromSummaryRow($row);

        //     dd([
        //         'row' => $row,
        //         'generated_key' => $key,
        //         'exists_in_meta' => isset($packingMetaByKey[$key]),
        //         'available_keys' => array_keys($packingMetaByKey),
        //     ]);
        // });

        $rows = collect($ciGoodsSummary['rows'] ?? [])->values()->map(function ($row, $index) use ($packingMetaRows, $packingMetaByKey) {

            $packingMeta = $packingMetaRows[$index] ?? $packingMetaByKey[$this->makePackingMetaKeyFromSummaryRow($row)] ?? [
                'bag_condition' => 'N/A',
                'bag_type' => (string) ($row['bag_type'] ?? 'N/A'),
                'empty_bag_weight_gram' => 0,
                'container_count' => 0,
                'stuffing_in_container' => 0,
            ];

            // dd($packingMeta);

            $rowContainerCount = $this->resolveContainerCountForSummaryRow($row, $packingMeta);

            $packingText = trim((string) ($row['packing_text'] ?? ''));

            // prevent duplicate KG
            if ($packingText && !str_contains(strtoupper($packingText), 'KG')) {
                $packingText .= ' KG';
            }

            $tareWeight = (float) ($packingMeta['empty_bag_weight_gram'] ?? 0);

            $description =
                'PACKED IN ' . strtoupper((string) ($packingMeta['bag_condition'] ?? 'N/A')) . ' ' .
                strtoupper((string) ($packingMeta['bag_type'] ?? 'N/A')) . ' BAGS' .
                ($packingText ? ' OF ABOUT ' . $packingText . ' NET EACH' : '') .
                ($tareWeight > 0 ? ', TARE WEIGHT AT ' . number_format($tareWeight, 0) . ' GRAMS' : '') . '.';

            $descriptionLines = [
                number_format((float) ($row['quantity_mt'] ?? 0), 3) . ' MT IN ' . number_format($rowContainerCount) . ' CONTAINERS',
                strtoupper((string) ($row['product_visual_name'] ?? 'N/A')),
                'PACKING:',
                $description,
            ];

            return [
                'serial_no' => $index + 1,
                'shipping_marks' => 'N/M',
                'bags' => (int) round((float) ($row['no_of_bags'] ?? 0)),
                'container_number' => 'ATTACHED SHEET',
                'description_lines' => array_filter($descriptionLines),
                'net_weight_mt' => round(((float) ($row['net_weight_kg'] ?? 0)) / 1000, 3),
                'gross_weight_mt' => round(((float) ($row['gross_weight_kg'] ?? 0)) / 1000, 3),
            ];
        })->all();

        $totalQuantityMt = round((float) ($ciGoodsSummary['totals']['quantity_mt'] ?? 0), 3);
        $totalNetWeightMt = round(((float) ($ciGoodsSummary['totals']['net_weight_kg'] ?? 0)) / 1000, 3);
        $totalGrossWeightMt = round(((float) ($ciGoodsSummary['totals']['gross_weight_kg'] ?? 0)) / 1000, 3);
        $totalBags = (int) round(collect($ciGoodsSummary['rows'] ?? [])->sum('no_of_bags'));
        $containerCount = $this->resolveContainerCountFromExportOrder($commercialInvoice->exportOrder);
        $buyerBlock = collect([
            $ciPreview['customer_name'] ?? null,
            $ciPreview['customer_address'] ?? null,
            !empty($ciPreview['customer_phone']) ? 'Phone: ' . $ciPreview['customer_phone'] : null,
        ])->filter()->implode("\n");

        $preview = [
            'commercial_invoice_no' => $commercialInvoice->invoice_no ?: $commercialInvoice->commercial_invoice_no,
            'commercial_invoice_date' => optional($commercialInvoice->invoice_date)->format('Y-m-d'),
            'port_of_loading' => $ciPreview['port_of_loading'] ?? 'N/A',
            'contents' => $ciPreview['product_original_name'] ?? $ciPreview['contents'] ?? 'N/A',
            'contract_po_dc_no' => $ciPreview['export_order_no'] ?? $ciPreview['contract_no'] ?? 'N/A',
            'export_order_date' => optional($commercialInvoice->exportOrder?->voucher_date)->format('Y-m-d'),
            'port_of_discharge' => $ciPreview['port_of_discharge'] ?? 'N/A',
            'export_form_no' => $ciPreview['form_e_no'] ?? 'N/A',
            'export_form_date' => $ciPreview['form_e_date'] ?? 'N/A',
            'bill_of_lading_no' => $ciPreview['bill_of_lading_no'] ?? 'N/A',
            'bill_of_lading_date' => $ciPreview['bill_of_lading_date'] ?? 'N/A',
            'final_destination' => $ciPreview['port_of_discharge'] ?? 'N/A',
            'vessel_name' => $ciPreview['vessel_name'] ?? 'N/A',
            'country_of_origin' => $ciPreview['origin_name'] ?? 'Pakistan',
            'payment_terms' => $ciPreview['payment_terms'] ?? 'N/A',
            'buyer_block' => $buyerBlock,
            'quantity_summary' => number_format($totalQuantityMt, 3) . ' MT IN ' . number_format($containerCount) . ' CONTAINERS',
            'total_bags_summary' => number_format($totalBags) . ' BAGS',
            'shipping_marks' => 'N/M',
            'total_quantity_mt' => $totalQuantityMt,
            'total_net_weight_mt' => $totalNetWeightMt,
            'total_gross_weight_mt' => $totalGrossWeightMt,
            'total_bags' => $totalBags,
        ];

        $goodsSummary = [
            'rows' => $rows,
            'totals' => [
                'quantity_mt' => $totalQuantityMt,
                'net_weight_mt' => $totalNetWeightMt,
                'gross_weight_mt' => $totalGrossWeightMt,
                'total_bags' => $totalBags,
            ],
        ];

        return [$commercialInvoice, $preview, $goodsSummary];
    }

    protected function buildPackingMetaRowsFromExportOrder(?ExportOrder $exportOrder): array
    {
        $packingItems = $exportOrder?->packingItems ?? collect();

        return $packingItems
            ->sortBy('id')
            ->values()
            ->map(function ($item) {

                // dd($item->toArray());
    
                $tareWeight = (float) ($item->min_weight_empty_bags ?? 0);
                $stuffingInContainer = (float) ($item->stuffing_in_container ?? 0);
                $metricTons = (float) ($item->metric_tons ?? 0);
                $containerCount = (float) ($item->no_of_containers ?? 0);

                if ($tareWeight <= 0) {
                    $tareWeight = (float) ($item->subItems->pluck('empty_bag_weight')->filter()->first() ?? 0);
                }

                if ($containerCount <= 0 && $stuffingInContainer > 0 && $metricTons > 0) {
                    $containerCount = $metricTons / $stuffingInContainer;
                }

                return [
                    'key' => $this->makePackingMetaKeyFromPackingItem($item),
                    'bag_condition' => $item->bagCondition?->name
                        ?? optional(getBagConditionById($item->bag_condition_id))->name
                        ?? 'N/A',
                    'bag_type' => $item->bagType?->name ?? (bag_type_name($item->bag_type_id) ?: 'N/A'),
                    'empty_bag_weight_gram' => round($tareWeight, 2),
                    'container_count' => (int) round($containerCount),
                    'stuffing_in_container' => round($stuffingInContainer, 4),
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizePackingTextForPackingList($packing): string
    {
        $value = trim((string) explode(',', (string) $packing)[0]);

        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return $value;
    }

    protected function makePackingMetaKeyFromPackingItem($item): string
    {
        $brandName = $item->brand?->name ?? (getBrandById($item->brand_id)?->name ?? 'N/A');
        $bagTypeName = $item->bagType?->name ?? (bag_type_name($item->bag_type_id) ?: 'N/A');

        return implode('|', [
            strtoupper(trim((string) $brandName)),
            $this->normalizePackingTextForPackingList($item->bag_size ?? ''),
            strtoupper(trim((string) $bagTypeName)),
        ]);
    }

    protected function makePackingMetaKeyFromSummaryRow(array $row): string
    {
        return implode('|', [
            strtoupper(trim((string) ($row['brand_name'] ?? 'N/A'))),
            $this->normalizePackingTextForPackingList($row['packing_text'] ?? ''),
            strtoupper(trim((string) ($row['bag_type'] ?? 'N/A'))),
        ]);
    }

    protected function resolveContainerCountFromExportOrder(?ExportOrder $exportOrder): int
    {
        return (int) round(collect($this->buildPackingMetaRowsFromExportOrder($exportOrder))->sum('container_count'));
    }

    protected function resolveContainerCountForSummaryRow(array $row, array $packingMeta): int
    {
        $containerCount = (float) ($packingMeta['container_count'] ?? 0);

        if ($containerCount > 0) {
            return (int) round($containerCount);
        }

        $stuffingInContainer = (float) ($packingMeta['stuffing_in_container'] ?? 0);
        $quantityMt = (float) ($row['quantity_mt'] ?? 0);

        if ($stuffingInContainer > 0 && $quantityMt > 0) {
            return (int) round($quantityMt / $stuffingInContainer);
        }

        return 0;
    }
}
