<?php

namespace App\Http\Controllers\Export;

use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportOrder;
use App\Models\Export\PackingList;
use App\Models\Export\ShipmentAdvise;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentAdviseController extends PackingListController
{
    public function index(): View
    {
        return view('management.export.shipment-advise.index');
    }

    public function getList(Request $request): View
    {
        $search = trim((string) $request->get('search'));

        $shipmentAdvises = ShipmentAdvise::with([
            'packingList.commercialInvoice.exportOrder',
            'commercialInvoice.exportOrder',
            'billOfLading',
        ])
            ->when($search !== '', function ($query) use ($search) {
                $plainSearch = str_replace('PL-', '', strtoupper($search));

                $query->where(function ($q) use ($search, $plainSearch) {
                    $q->whereHas('commercialInvoice', function ($subQ) use ($search) {
                        $subQ->where('commercial_invoice_no', 'like', "%{$search}%")
                            ->orWhere('invoice_no', 'like', "%{$search}%")
                            ->orWhereHas('exportOrder', function ($eoQ) use ($search) {
                                $eoQ->where('voucher_no', 'like', "%{$search}%")
                                    ->orWhere('contract_no', 'like', "%{$search}%");
                            });
                    })->orWhereHas('packingList', function ($subQ) use ($plainSearch) {
                        $subQ->where('id', 'like', "%{$plainSearch}%");
                    })->orWhereHas('billOfLading', function ($subQ) use ($search) {
                        $subQ->where('bill_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        $shipmentAdvises->getCollection()->transform(function (ShipmentAdvise $shipmentAdvise) {
            [, $preview] = $this->resolveShipmentAdvisePayload($shipmentAdvise);
            $shipmentAdvise->computed_preview = $preview;

            return $shipmentAdvise;
        });

        return view('management.export.shipment-advise.getList', compact('shipmentAdvises'));
    }

    public function create(): View
    {
        return view('management.export.shipment-advise.create', [
            'shipmentAdvise' => null,
            'exportOrders' => $this->getEligibleExportOrders(),
            'preview' => null,
            'goodsSummary' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateShipmentAdvise($request);

        DB::beginTransaction();

        try {
            [$packingList, $preview, $goodsSummary] = $this->buildPayloadFromPackingListId(
                (int) $validated['packing_list_id']
            );

            ShipmentAdvise::create([
                'packing_list_id' => $packingList->id,
                'commercial_invoice_id' => $packingList->commercial_invoice_id,
                'bill_of_lading_id' => $packingList->bill_of_lading_id,
                'remarks' => null,
                'snapshot_data' => $preview,
                'goods_summary' => $goodsSummary,
                'created_by' => auth()->user()?->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Shipment Advise has been created']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id): View
    {
        $shipmentAdvise = ShipmentAdvise::with([
            'packingList.commercialInvoice.exportOrder',
            'commercialInvoice.exportOrder',
            'billOfLading',
        ])->findOrFail($id);

        [, $preview, $goodsSummary] = $this->resolveShipmentAdvisePayload($shipmentAdvise);

        return view('management.export.shipment-advise.show', [
            'shipmentAdvise' => $shipmentAdvise,
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function edit($id): View
    {
        $shipmentAdvise = ShipmentAdvise::with([
            'packingList.commercialInvoice.exportOrder',
            'commercialInvoice.exportOrder',
            'billOfLading',
        ])->findOrFail($id);

        [, $preview, $goodsSummary] = $this->resolveShipmentAdvisePayload($shipmentAdvise);

        return view('management.export.shipment-advise.edit', [
            'shipmentAdvise' => $shipmentAdvise,
            'exportOrders' => $this->getEligibleExportOrders($shipmentAdvise->id),
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $shipmentAdvise = ShipmentAdvise::lockForUpdate()->find($id);

            if (!$shipmentAdvise) {
                DB::rollBack();

                return response()->json(['error' => 'Shipment Advise already deleted or not found.'], 404);
            }

            $validated = $this->validateShipmentAdvise($request, $shipmentAdvise->id);

            [$packingList, $preview, $goodsSummary] = $this->buildPayloadFromPackingListId(
                (int) $validated['packing_list_id'],
                $shipmentAdvise
            );

            $shipmentAdvise->update([
                'packing_list_id' => $packingList->id,
                'commercial_invoice_id' => $packingList->commercial_invoice_id,
                'bill_of_lading_id' => $packingList->bill_of_lading_id,
                'remarks' => null,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
                'snapshot_data' => $preview,
                'goods_summary' => $goodsSummary,
            ]);

            DB::commit();

            return response()->json(['message' => 'Shipment Advise has been updated']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $shipmentAdvise = ShipmentAdvise::lockForUpdate()->find($id);

            if (!$shipmentAdvise) {
                DB::rollBack();

                return response()->json(['error' => 'Shipment Advise not found.'], 404);
            }

            $shipmentAdvise->delete();

            DB::commit();

            return response()->json(['message' => 'Shipment Advise has been deleted']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRelatedData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'packing_list_id' => ['required', 'integer', 'exists:packing_lists,id'],
        ]);

        [, $preview, $goodsSummary] = $this->buildPayloadFromPackingListId((int) $validated['packing_list_id']);

        return response()->json([
            'success' => true,
            'preview_html' => view('management.export.shipment-advise.preview', [
                'preview' => $preview,
                'goodsSummary' => $goodsSummary,
            ])->render(),
            'preview' => $preview,
            'goods_summary' => $goodsSummary,
        ]);
    }

    public function getPackingListsByExportOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_order_id' => ['required', 'integer', 'exists:export_orders,id'],
            'current_shipment_advise_id' => ['nullable', 'integer', 'exists:shipment_advises,id'],
        ]);

        $packingLists = $this->getEligiblePackingListsByExportOrder(
            (int) $validated['export_order_id'],
            isset($validated['current_shipment_advise_id']) ? (int) $validated['current_shipment_advise_id'] : null
        )->map(function (PackingList $packingList) {
            $invoiceNo = $packingList->commercialInvoice?->invoice_no
                ?: $packingList->commercialInvoice?->commercial_invoice_no
                ?: 'N/A';

            return [
                'id' => $packingList->id,
                'text' => 'PL-' . str_pad((string) $packingList->id, 4, '0', STR_PAD_LEFT) . ' / INV: ' . $invoiceNo,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $packingLists,
        ]);
    }

    protected function validateShipmentAdvise(Request $request, ?int $shipmentAdviseId = null): array
    {
        return $request->validate([
            'packing_list_id' => [
                'required',
                'integer',
                'exists:packing_lists,id',
                Rule::unique('shipment_advises', 'packing_list_id')->ignore($shipmentAdviseId),
            ],
        ]);
    }

    protected function getEligiblePackingListsByExportOrder(int $exportOrderId, ?int $currentShipmentAdviseId = null)
    {
        $takenIds = ShipmentAdvise::query()
            ->when($currentShipmentAdviseId, fn($q) => $q->where('id', '!=', $currentShipmentAdviseId))
            ->pluck('packing_list_id')
            ->filter()
            ->values()
            ->all();

        return PackingList::with(['commercialInvoice'])
            ->where('export_order_id', $exportOrderId)
            ->whereNotIn('id', $takenIds)
            ->latest()
            ->get();
    }

    protected function getEligibleExportOrders(?int $currentShipmentAdviseId = null)
    {
        $takenIds = ShipmentAdvise::query()
            ->when($currentShipmentAdviseId, fn($q) => $q->where('id', '!=', $currentShipmentAdviseId))
            ->pluck('packing_list_id')
            ->filter()
            ->values()
            ->all();

        return ExportOrder::with(['buyer'])
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->whereHas('packingLists', function ($query) use ($takenIds) {
                $query->when(!empty($takenIds), function ($subQuery) use ($takenIds) {
                    $subQuery->whereNotIn('id', $takenIds);
                });
            })
            ->latest()
            ->get();
    }

    protected function resolveShipmentAdvisePayload(ShipmentAdvise $shipmentAdvise): array
    {
        if (!empty($shipmentAdvise->snapshot_data) && !empty($shipmentAdvise->goods_summary)) {
            return [$shipmentAdvise->packingList, $shipmentAdvise->snapshot_data, $shipmentAdvise->goods_summary];
        }

        return $this->buildPayloadFromPackingListId((int) $shipmentAdvise->packing_list_id, $shipmentAdvise);
    }

    protected function buildPayloadFromPackingListId(
        int $packingListId,
        ?ShipmentAdvise $shipmentAdvise = null
    ): array {
        $packingList = PackingList::with([
            'commercialInvoice.exportOrder.company',
            'commercialInvoice.exportOrder.product',
            'commercialInvoice.exportOrder.portOfLoading.country',
            'commercialInvoice.exportOrder.portOfDischarge.country',
            'commercialInvoice.exportOrder.originCountry',
            'commercialInvoice.exportOrder.buyer',
            'commercialInvoice.exportOrder.packingItems.brand',
            'commercialInvoice.exportOrder.packingItems.bagCondition',
            'commercialInvoice.exportOrder.packingItems.bagType',
            'commercialInvoice.exportOrder.packingItems.subItems',
            'billOfLading',
        ])->findOrFail($packingListId);

        $commercialInvoice = $packingList->commercialInvoice;

        if (!$commercialInvoice) {
            abort(422, 'Packing List does not have a linked Commercial Invoice.');
        }

        [, $invoicePreview, $invoiceGoodsSummary] = $this->buildPayloadFromInvoice($commercialInvoice);

        $exportOrder = $commercialInvoice->exportOrder;
        $shipmentDate = optional($shipmentAdvise?->created_at)->format('Y-m-d')
            ?: Carbon::today()->format('Y-m-d');
        $lineItems = $this->buildShipmentAdviseLineItems($invoiceGoodsSummary, $exportOrder);
        $isMultipleItems = count($lineItems) > 1;
        $schedule = $this->resolveShipmentSchedule($packingList);

        $totals = [
            'quantity_mt' => round((float) ($invoiceGoodsSummary['totals']['quantity_mt'] ?? 0), 3),
            'net_weight_mt' => round(((float) ($invoiceGoodsSummary['totals']['net_weight_kg'] ?? 0)) / 1000, 3),
            'gross_weight_mt' => round(((float) ($invoiceGoodsSummary['totals']['gross_weight_kg'] ?? 0)) / 1000, 3),
            'total_bags' => (int) round(collect($invoiceGoodsSummary['rows'] ?? [])->sum('no_of_bags')),
        ];

        $singleItem = $lineItems[0] ?? null;
        $buyerBlock = collect([
            $invoicePreview['customer_name'] ?? null,
            $invoicePreview['customer_address'] ?? null,
        ])->filter()->implode("\n");

        $preview = [
            'document_date' => $shipmentDate,
            'reference_no' => $this->resolveShipmentReferenceNo($packingList, $invoicePreview),
            'packing_list_no' => 'PL-' . str_pad((string) $packingList->id, 4, '0', STR_PAD_LEFT),
            'packing_list_date' => optional($packingList->created_at)->format('Y-m-d'),
            'buyer_block' => $buyerBlock,
            'company_name' => $invoicePreview['company_name'] ?? 'N/A',
            'company_address' => $invoicePreview['company_address'] ?? 'N/A',
            'company_phone' => $invoicePreview['company_phone'] ?? null,
            'is_multiple_items' => $isMultipleItems,
            'line_items' => $lineItems,
            'quantity_text' => $isMultipleItems ? null : ($packingList->snapshot_data['quantity_summary'] ?? $singleItem['quantity_text'] ?? 'N/A'),
            'packing_text' => $isMultipleItems ? null : ($singleItem['single_packing_text'] ?? 'N/A'),
            'bag_marking_text' => $isMultipleItems ? null : ($singleItem['bag_marking'] ?? 'N/A'),
            'description_of_goods' => $isMultipleItems ? null : ($singleItem['description'] ?? 'N/A'),
            'number_of_bags_text' => $isMultipleItems ? null : ($singleItem['number_of_bags_text'] ?? 'N/A'),
            'bag_markings' => $this->buildBagMarkingsText($lineItems, $isMultipleItems),
            'gross_weight_text' => $this->formatMetricTons($totals['gross_weight_mt']),
            'net_weight_text' => $this->formatMetricTons($totals['net_weight_mt']),
            'vessel_name' => $invoicePreview['vessel_name'] ?? 'N/A',
            'etd_from_loading_port' => $schedule['etd'],
            'eta_at_discharge_port' => $schedule['eta'],
            'loading_port' => $invoicePreview['port_of_loading'] ?? 'N/A',
            'discharge_port' => $schedule['discharge_port'] ?: ($invoicePreview['port_of_discharge'] ?? 'N/A'),
            'bill_of_lading_no' => $invoicePreview['bill_of_lading_no'] ?? 'N/A',
            'bill_of_lading_date' => $invoicePreview['bill_of_lading_date'] ?? 'N/A',
            'quantity_summary' => $packingList->snapshot_data['quantity_summary'] ?? ($invoicePreview['quantity_summary'] ?? 'N/A'),
            'total_bags_summary' => number_format($totals['total_bags']) . ' BAGS',
            'total_quantity_mt' => $totals['quantity_mt'],
            'total_net_weight_mt' => $totals['net_weight_mt'],
            'total_gross_weight_mt' => $totals['gross_weight_mt'],
            'total_bags' => $totals['total_bags'],
        ];

        $goodsSummary = [
            'rows' => $lineItems,
            'totals' => $totals,
        ];

        return [$packingList, $preview, $goodsSummary];
    }

    protected function buildShipmentAdviseLineItems(array $invoiceGoodsSummary, ?ExportOrder $exportOrder): array
    {
        $packingMetaRows = $this->buildPackingMetaRowsFromExportOrder($exportOrder);
        $packingMetaByKey = collect($packingMetaRows)
            ->keyBy(fn($row) => $row['key'])
            ->all();

        return collect($invoiceGoodsSummary['rows'] ?? [])
            ->values()
            ->map(function (array $row, int $index) use ($packingMetaRows, $packingMetaByKey) {
                $packingMeta = $packingMetaRows[$index]
                    ?? $packingMetaByKey[$this->makePackingMetaKeyFromSummaryRow($row)]
                    ?? [
                        'bag_condition' => 'N/A',
                        'bag_type' => (string) ($row['bag_type'] ?? 'N/A'),
                    ];

                $packingSizeText = $this->normalizePackingTextForPackingList($row['packing_text'] ?? '');
                $packingSizeLabel = $this->formatPackingSizeLabel($packingSizeText);
                $bagCondition = strtoupper(trim((string) ($packingMeta['bag_condition'] ?? '')));
                $bagType = strtoupper(trim((string) ($packingMeta['bag_type'] ?? ($row['bag_type'] ?? 'N/A'))));
                $masterPackingText = strtoupper(trim((string) ($row['master_packing_text'] ?? '')));
                $quantityMt = round((float) ($row['quantity_mt'] ?? 0), 3);
                $bags = (int) round((float) ($row['no_of_bags'] ?? 0));
                $bagMarking = strtoupper(trim((string) ($row['brand_name'] ?? 'N/M'))) ?: 'N/M';

                $multiPackingText = trim(
                    ($packingSizeLabel !== '' ? $packingSizeLabel . ' ' : '')
                    . ($bagType !== '' ? $bagType . ' BAGS' : 'BAGS')
                    . ($masterPackingText !== '' ? ' STUFFED IN ' . $masterPackingText . ' BAG' : '')
                );

                $singlePackingText = 'PACKED IN ';

                if ($bagCondition !== '' && $bagCondition !== 'N/A') {
                    $singlePackingText .= $bagCondition . ' ';
                }

                $singlePackingText .= ($bagType !== '' ? $bagType . ' BAGS' : 'BAGS');

                if ($packingSizeLabel !== '') {
                    $singlePackingText .= ' OF ' . $packingSizeLabel . ' NET EACH';
                }

                if ($masterPackingText !== '') {
                    $singlePackingText .= ' STUFFED IN ' . $masterPackingText . ' BAG';
                }

                $singlePackingText = rtrim(preg_replace('/\s+/', ' ', strtoupper($singlePackingText)), '.') . '.';

                $numberOfBagsLines = [];
                if ($bags > 0 && $packingSizeLabel !== '') {
                    $numberOfBagsLines[] = number_format($bags) . ' BAGS OF ' . $packingSizeLabel;
                } elseif ($bags > 0) {
                    $numberOfBagsLines[] = number_format($bags) . ' BAGS';
                }

                if ($masterPackingText !== '' && $quantityMt > 0) {
                    $numberOfBagsLines[] = $this->formatQuantityNumber($quantityMt) . ' ' . $this->pluralizeMasterPacking($masterPackingText, $quantityMt);
                }

                return [
                    'label' => $this->indexToAlphabetLabel($index),
                    'quantity_text' => $this->formatQuantityNumber($quantityMt) . ' MTS',
                    'packing_text' => $multiPackingText !== '' ? strtoupper($multiPackingText) : 'N/A',
                    'single_packing_text' => $singlePackingText,
                    'bag_marking' => $bagMarking,
                    'description' => strtoupper((string) ($row['product_visual_name'] ?? ($exportOrder?->visual_name ?: 'N/A'))),
                    'number_of_bags_lines' => $numberOfBagsLines,
                    'number_of_bags_text' => implode(' / ', $numberOfBagsLines) ?: 'N/A',
                    'net_weight_mt' => round(((float) ($row['net_weight_kg'] ?? 0)) / 1000, 3),
                    'gross_weight_mt' => round(((float) ($row['gross_weight_kg'] ?? 0)) / 1000, 3),
                    'bags' => $bags,
                ];
            })
            ->all();
    }

    protected function resolveShipmentSchedule(PackingList $packingList): array
    {
        $billOfLading = $packingList->billOfLading;
        $deliveryOrderIds = collect($billOfLading?->selected_delivery_order_ids ?? [])
            ->push($billOfLading?->delivery_order_id)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($deliveryOrderIds->isEmpty() && $billOfLading) {
            $deliveryOrderIds = collect($billOfLading->selected_delivery_challan_ids ?? [])
                ->push($billOfLading->export_delivery_challan_id)
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->pipe(function (Collection $challanIds) {
                    if ($challanIds->isEmpty()) {
                        return collect();
                    }

                    return \App\Models\Export\ExportDeliveryChallan::with('delivery_order')
                        ->whereIn('id', $challanIds)
                        ->get()
                        ->flatMap->delivery_order
                        ->pluck('id')
                        ->filter()
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values();
                });
        }

        $deliveryOrders = $deliveryOrderIds->isEmpty()
            ? collect()
            : ExportDeliveryOrder::with('locations.companyLocation')
                ->whereIn('id', $deliveryOrderIds)
                ->get();

        $etd = $deliveryOrders->pluck('vessel_etd')
            ->filter()
            ->map(fn($date) => Carbon::parse($date)->format('d.m.Y'))
            ->unique()
            ->implode(' / ') ?: 'N/A';

        $eta = $deliveryOrders->pluck('vessel_eta')
            ->filter()
            ->map(fn($date) => Carbon::parse($date)->format('d.m.Y'))
            ->unique()
            ->implode(' / ') ?: 'N/A';

        $dischargePort = $deliveryOrders->flatMap(function (ExportDeliveryOrder $deliveryOrder) {
            return $deliveryOrder->locations->map(function ($location) {
                return optional($location->companyLocation)->name;
            });
        })->filter()->unique()->implode(' / ');

        return [
            'etd' => $etd,
            'eta' => $eta,
            'discharge_port' => $dischargePort,
        ];
    }

    protected function resolveShipmentReferenceNo(PackingList $packingList, array $invoicePreview): string
    {
        return $invoicePreview['export_order_no']
            ?? $invoicePreview['contract_no']
            ?? $invoicePreview['commercial_invoice_no']
            ?? ('PL-' . str_pad((string) $packingList->id, 4, '0', STR_PAD_LEFT));
    }

    protected function buildBagMarkingsText(array $lineItems, bool $isMultipleItems): string
    {
        if (!$isMultipleItems) {
            return $lineItems[0]['bag_marking'] ?? 'N/M';
        }

        return collect($lineItems)
            ->map(function (array $item) {
                return ($item['label'] ?? '-') . ') ' . ($item['bag_marking'] ?? 'N/M');
            })
            ->implode("\n");
    }

    protected function formatMetricTons(float $value): string
    {
        return number_format($value, 3) . ' M.TONS';
    }

    protected function formatQuantityNumber(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0)
            : number_format($value, 3);
    }

    protected function formatPackingSizeLabel(string $packingText): string
    {
        $packingText = trim($packingText);

        if ($packingText === '') {
            return '';
        }

        return is_numeric($packingText)
            ? rtrim(rtrim(number_format((float) $packingText, 3, '.', ''), '0'), '.') . ' KG'
            : strtoupper($packingText);
    }

    protected function pluralizeMasterPacking(string $masterPackingText, float $quantityMt): string
    {
        $label = strtoupper(trim($masterPackingText));

        if ($label === '') {
            return '';
        }

        if ($quantityMt > 1 && !str_ends_with($label, 'S')) {
            $label .= 'S';
        }

        return $label;
    }

    protected function indexToAlphabetLabel(int $index): string
    {
        $alphabet = range('A', 'Z');

        return $alphabet[$index] ?? (string) ($index + 1);
    }
}
