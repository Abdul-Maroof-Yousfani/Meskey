<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\BillOfLading;
use App\Models\Export\ExportDeliveryChallan;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrder;
use App\Models\Master\CompanyLocation;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillOfLadingController extends Controller
{
    public function index(): View
    {
        return view('management.export.bill-of-lading.index');
    }

    public function getList(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $billOfLadings = BillOfLading::with(['exportOrder', 'exportDeliveryChallan.customer', 'deliveryOrder'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('bill_no', 'like', "%{$search}%")
                        ->orWhere('carrier_name', 'like', "%{$search}%")
                        ->orWhereHas('exportOrder', function ($subQ) use ($search) {
                            $subQ->where('voucher_no', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.bill-of-lading.getList', compact('billOfLadings'));
    }

    public function create(): View
    {
        return view('management.export.bill-of-lading.create', [
            'billOfLading' => null,
            'exportOrders' => $this->getApprovedExportOrders(),
            'preview' => null,
            'goodsSummary' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateBillOfLading($request);

        DB::beginTransaction();

        try {
            [$exportOrder, $formEs, $deliveryChallans, $deliveryOrders] = $this->resolveSelections($validated);
            $this->ensureFormEsAreAvailable($formEs->pluck('id')->all(), null);
            $this->ensureChallansAreAvailable($deliveryChallans->pluck('id')->all(), null);
            [$preview, $goodsSummary] = $this->buildDocumentPayload($exportOrder, $formEs, $deliveryChallans, $validated);

            $billOfLading = \Illuminate\Support\Facades\Cache::lock('export_bol_generation', 10)->block(5, function () use ($request, $exportOrder, $formEs, $deliveryChallans, $deliveryOrders, $validated, $preview, $goodsSummary) {
                // Re-generate bill_no server-side to ensure uniqueness using the existing getNumber method
                $bill_no = $this->getNumber($request)->getData()->bill_no;

                return BillOfLading::create([
                    'export_delivery_challan_id' => $deliveryChallans->first()?->id,
                    'delivery_order_id' => $deliveryOrders->first()?->id,
                    'export_order_id' => $exportOrder->id,
                    'selected_form_e_ids' => $formEs->pluck('id')->values()->all(),
                    'selected_delivery_challan_ids' => $deliveryChallans->pluck('id')->values()->all(),
                    'selected_delivery_order_ids' => $deliveryOrders->pluck('id')->values()->all(),
                    'company_id' => $exportOrder->company_id,
                    'customer_id' => $deliveryOrders->first()?->customer_id,
                    'bill_no' => $bill_no,
                    'bill_date' => $validated['bill_date'] ?? null,
                    'carrier_name' => $validated['carrier_name'] ?? null,
                    'shipped_on_board_date' => $validated['shipped_on_board_date'] ?? null,
                    'charter_party_dated' => $validated['charter_party_dated'] ?? null,
                    'cautions_text' => $validated['cautions_text'] ?? null,
                    'place_of_issue' => $preview['place_of_issue'] ?? null,
                    'snapshot_data' => $preview,
                    'goods_summary' => $goodsSummary,
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'created_by' => auth()->user()?->id,
                ]);
            });

            DB::commit();

            return response()->json(['message' => 'Bill of Lading has been created']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id): View
    {
        $billOfLading = BillOfLading::with(['exportOrder', 'exportDeliveryChallan'])->findOrFail($id);
        [$preview, $goodsSummary] = $this->resolveDocumentPayloadFromBillOfLading($billOfLading);

        return view('management.export.bill-of-lading.show', [
            'billOfLading' => $billOfLading,
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function edit($id): View
    {
        $billOfLading = BillOfLading::with(['exportOrder'])->findOrFail($id);
        [$preview, $goodsSummary] = $this->resolveDocumentPayloadFromBillOfLading($billOfLading);

        return view('management.export.bill-of-lading.edit', [
            'billOfLading' => $billOfLading,
            'exportOrders' => $this->getApprovedExportOrders($billOfLading->id),
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $billOfLading = BillOfLading::lockForUpdate()->find($id);

            if (!$billOfLading) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Bill of Lading already deleted or not found.'
                ], 404);
            }

            $validated = $this->validateBillOfLading($request, $billOfLading->id);

            [$exportOrder, $formEs, $deliveryChallans, $deliveryOrders] = $this->resolveSelections($validated);
            $this->ensureFormEsAreAvailable($formEs->pluck('id')->all(), $billOfLading->id);
            $this->ensureChallansAreAvailable($deliveryChallans->pluck('id')->all(), $billOfLading->id);
            [$preview, $goodsSummary] = $this->buildDocumentPayload($exportOrder, $formEs, $deliveryChallans, $validated);

            $billOfLading->update([
                'export_delivery_challan_id' => $deliveryChallans->first()?->id,
                'delivery_order_id' => $deliveryOrders->first()?->id,
                'export_order_id' => $exportOrder->id,
                'selected_form_e_ids' => $formEs->pluck('id')->values()->all(),
                'selected_delivery_challan_ids' => $deliveryChallans->pluck('id')->values()->all(),
                'selected_delivery_order_ids' => $deliveryOrders->pluck('id')->values()->all(),
                'company_id' => $exportOrder->company_id,
                'customer_id' => $deliveryOrders->first()?->customer_id,
                'bill_no' => $validated['bill_no'],
                'bill_date' => $validated['bill_date'] ?? null,
                'carrier_name' => $validated['carrier_name'] ?? null,
                'shipped_on_board_date' => $validated['shipped_on_board_date'] ?? null,
                'charter_party_dated' => $validated['charter_party_dated'] ?? null,
                'cautions_text' => $validated['cautions_text'] ?? null,
                'place_of_issue' => $preview['place_of_issue'] ?? null,
                'snapshot_data' => $preview,
                'goods_summary' => $goodsSummary,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
            ]);

            DB::commit();

            return response()->json(['message' => 'Bill of Lading has been updated']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $billOfLading = BillOfLading::lockForUpdate()->find($id);

            if (!$billOfLading) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Bill of Lading already deleted or not found.'
                ], 404);
            }

            $billOfLading->delete();

            DB::commit();

            return response()->json(['message' => 'Bill of Lading has been deleted']);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRelatedData(Request $request): JsonResponse
    {
        $request->merge([
            'export_form_e_ids' => array_values(array_unique(Arr::wrap($request->input('export_form_e_ids', [])))),
            'export_delivery_challan_ids' => array_values(array_unique(Arr::wrap($request->input('export_delivery_challan_ids', [])))),
        ]);

        $validated = $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'export_form_e_ids' => ['required', 'array', 'min:1'],
            'export_form_e_ids.*' => ['integer', 'exists:export_form_es,id'],
            'export_delivery_challan_ids' => ['required', 'array', 'min:1'],
            'export_delivery_challan_ids.*' => ['integer', 'exists:delivery_challans,id'],
            'bill_no' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['nullable', 'date'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'shipped_on_board_date' => ['nullable', 'date'],
            'charter_party_dated' => ['nullable', 'date'],
            'cautions_text' => ['nullable', 'string'],
            'current_bill_id' => ['nullable', 'integer', 'exists:bill_of_ladings,id'],
        ]);

        [$exportOrder, $formEs, $deliveryChallans, $deliveryOrders] = $this->resolveSelections($validated);
        $this->ensureFormEsAreAvailable($formEs->pluck('id')->all(), $validated['current_bill_id'] ?? null);
        $this->ensureChallansAreAvailable($deliveryChallans->pluck('id')->all(), $validated['current_bill_id'] ?? null);
        [$preview, $goodsSummary] = $this->buildDocumentPayload($exportOrder, $formEs, $deliveryChallans, $validated);

        return response()->json([
            'success' => true,
            'preview_html' => view('management.export.bill-of-lading.preview', [
                'preview' => $preview,
                'goodsSummary' => $goodsSummary,
            ])->render(),
            'preview' => $preview,
            'goods_summary' => $goodsSummary,
            'delivery_orders' => $deliveryOrders->map(function (ExportDeliveryOrder $deliveryOrder) {
                return [
                    'id' => $deliveryOrder->id,
                    'reference_no' => $deliveryOrder->reference_no,
                ];
            })->values(),
        ]);
    }

    public function getNumber(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->get('bill_date', now()->toDateString()));
        $prefix = 'BOL-' . $date->format('Y-m-d');
        $latest = BillOfLading::where('bill_no', 'like', $prefix . '-%')->latest('id')->first();
        $next = 1;

        if ($latest) {
            $parts = explode('-', $latest->bill_no);
            $next = ((int) end($parts)) + 1;
        }

        return response()->json([
            'success' => true,
            'bill_no' => $prefix . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT),
        ]);
    }

    public function getDeliveryChallansByFormEs(Request $request): JsonResponse
    {
        $request->merge([
            'export_form_e_ids' => Arr::wrap($request->input('export_form_e_ids', [])),
        ]);

        $validated = $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'export_form_e_ids' => ['required', 'array', 'min:1'],
            'export_form_e_ids.*' => ['integer', 'exists:export_form_es,id'],
            'current_bill_id' => ['nullable', 'integer', 'exists:bill_of_ladings,id'],
        ]);

        $challans = ExportDeliveryChallan::with(['customer'])
            ->where('am_approval_status', 'approved')
            ->whereHas('delivery_order', function ($q) use ($validated) {
                $q->where('delivery_order.export_order_id', $validated['export_order_id'])
                    ->whereIn('delivery_order.export_form_e_id', $validated['export_form_e_ids'])
                    ->where('delivery_order.am_approval_status', 'approved');
            })
            ->latest()
            ->get()
            ->filter(function ($challan) use ($validated) {
                return $this->isChallanAvailableForBill($challan->id, $validated['current_bill_id'] ?? null);
            })
            ->values()
            ->map(function ($challan) {
                return [
                    'id' => $challan->id,
                    'text' => $challan->dc_no . ' - ' . ($challan->customer?->name ?? 'N/A'),
                ];
            });

        return response()->json(['success' => true, 'data' => $challans]);
    }


    public function getFormEsByExportOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'current_bill_id' => ['nullable', 'integer', 'exists:bill_of_ladings,id'],
        ]);

        $formEs = ExportFormE::where('export_order_id', $validated['export_order_id'])
            ->whereIn('id', function ($query) use ($validated) {
                $query->select('delivery_order.export_form_e_id')
                    ->from('delivery_order')
                    ->join('delivery_challan_delivery_order', 'delivery_challan_delivery_order.delivery_order_id', '=', 'delivery_order.id')
                    ->join('delivery_challans', 'delivery_challans.id', '=', 'delivery_challan_delivery_order.delivery_challan_id')
                    ->where('delivery_order.type', 'export_order')
                    ->where('delivery_order.am_approval_status', 'approved')
                    ->where('delivery_challans.type', 'export_delivery_challan')
                    ->where('delivery_challans.am_approval_status', 'approved')
                    ->where('delivery_order.export_order_id', $validated['export_order_id']);
            })
            ->latest('id')
            ->get()
            ->filter(function (ExportFormE $formE) use ($validated) {
                // A Form-E is available if it has at least one DC available
                return ExportDeliveryChallan::whereHas('delivery_order', function ($q) use ($formE) {
                    $q->where('delivery_order.export_form_e_id', $formE->id);
                })->get()->contains(function ($challan) use ($validated) {
                    return $this->isChallanAvailableForBill($challan->id, $validated['current_bill_id'] ?? null);
                });
            })
            ->values()
            ->map(function (ExportFormE $formE) {
                return [
                    'id' => $formE->id,
                    'text' => trim($formE->form_e_no . ' - ' . ($formE->form_e_date ? Carbon::parse($formE->form_e_date)->format('d.m.Y') : 'N/A')),
                ];
            });

        return response()->json(['success' => true, 'data' => $formEs]);
    }

    protected function validateBillOfLading(Request $request, ?int $billId = null): array
    {
        $request->merge([
            'export_form_e_ids' => array_values(array_unique(Arr::wrap($request->input('export_form_e_ids', [])))),
            'export_delivery_challan_ids' => array_values(array_unique(Arr::wrap($request->input('export_delivery_challan_ids', [])))),
        ]);

        return $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'export_form_e_ids' => ['required', 'array', 'min:1'],
            'export_form_e_ids.*' => ['integer', 'exists:export_form_es,id'],
            'export_delivery_challan_ids' => ['required', 'array', 'min:1'],
            'export_delivery_challan_ids.*' => ['integer', 'exists:delivery_challans,id'],
            'bill_no' => ['required', 'string', 'max:255'],
            'bill_date' => ['nullable', 'date'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'shipped_on_board_date' => ['nullable', 'date'],
            'charter_party_dated' => ['nullable', 'date'],
            'cautions_text' => ['nullable', 'string'],
        ]);
    }

    protected function getApprovedExportOrders(?int $currentBolId = null)
    {
        $takenChallanIds = BillOfLading::query()
            ->when($currentBolId, fn($q) => $q->where('id', '!=', $currentBolId))
            ->get()
            ->flatMap(function ($bol) {
                $ids = is_array($bol->selected_delivery_challan_ids) ? $bol->selected_delivery_challan_ids : [];
                if ($bol->export_delivery_challan_id) {
                    $ids[] = $bol->export_delivery_challan_id;
                }
                return $ids;
            })->unique()->filter()->values()->all();

        return ExportOrder::with(['buyer'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->whereHas('deliveryOrders.delivery_challans', function ($q) use ($takenChallanIds) {
                $q->whereNotIn('delivery_challans.id', $takenChallanIds)
                    ->where('delivery_challans.am_approval_status', 'approved');
            })
            ->latest()
            ->get();
    }

    protected function resolveSelections(array $validated): array
    {
        $exportOrder = ExportOrder::with([
            'company',
            'consignee',
            'product',
            'portOfLoading.country',
            'portOfDischarge.country',
        ])->where('am_approval_status', 'approved')->findOrFail($validated['export_order_id']);

        $formEs = ExportFormE::where('export_order_id', $exportOrder->id)
            ->whereIn('id', $validated['export_form_e_ids'])
            ->get();

        if ($formEs->count() !== count($validated['export_form_e_ids'])) {
            abort(422, 'Selected Form-E does not belong to the selected Export Order.');
        }

        $deliveryChallans = ExportDeliveryChallan::with([
            'customer',
            'delivery_order.customer',
            'delivery_order.exportOrder.company',
            'delivery_order.exportOrder.portOfLoading.country',
            'delivery_order.exportOrder.portOfDischarge.country',
            'delivery_order.exportOrder.consignee',
            'delivery_order.exportOrder.product',
            'delivery_order.exportFormE',
            'delivery_challan_data.deliveryOrderData.brand',
            'delivery_challan_data.deliveryOrderData.bagType',
            'delivery_challan_data.product',
            'delivery_challan_data.loadingProgramItem.exportLoadingProgram',
        ])->where('am_approval_status', 'approved')
            ->whereIn('id', $validated['export_delivery_challan_ids'])
            ->get();

        if ($deliveryChallans->count() !== count($validated['export_delivery_challan_ids'])) {
            abort(422, 'Some selected Delivery Challans are invalid.');
        }

        $deliveryOrders = $deliveryChallans->flatMap(function ($challan) {
            return $challan->delivery_order;
        })->unique('id')->values();

        $invalidDo = $deliveryOrders->first(function ($deliveryOrder) use ($exportOrder, $formEs) {
            return (int) $deliveryOrder->export_order_id !== (int) $exportOrder->id
                || !$formEs->pluck('id')->contains((int) $deliveryOrder->export_form_e_id)
                || (string) $deliveryOrder->am_approval_status !== 'approved';
        });

        if ($invalidDo) {
            abort(422, 'Selected Delivery Challans do not match the selected Export Order / Form-E.');
        }

        $selectedFormEIds = $formEs->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        $coveredFormEIds = $deliveryOrders->pluck('export_form_e_id')->map(fn($id) => (int) $id)->filter()->unique()->values()->all();
        $uncoveredFormEIds = array_values(array_diff($selectedFormEIds, $coveredFormEIds));
        if (!empty($uncoveredFormEIds)) {
            abort(422, 'Please select at least one Delivery Challan against every selected Form-E.');
        }

        return [$exportOrder, $formEs, $deliveryChallans, $deliveryOrders];
    }

    protected function ensureChallansAreAvailable(array $challanIds, ?int $currentBillId): void
    {
        foreach ($challanIds as $challanId) {
            if (!$this->isChallanAvailableForBill($challanId, $currentBillId)) {
                abort(422, 'One or more selected Delivery Challans are already used in another Bill of Lading.');
            }
        }
    }

    protected function ensureFormEsAreAvailable(array $formEIds, ?int $currentBillId): void
    {
        foreach ($formEIds as $formEId) {
            if (!$this->isFormEAvailableForBill($formEId, $currentBillId)) {
                abort(422, 'One or more selected Form-E are already used in another Bill of Lading.');
            }
        }
    }

    protected function isChallanAvailableForBill(int $challanId, ?int $currentBillId): bool
    {
        return !BillOfLading::query()
            ->when($currentBillId, function ($query) use ($currentBillId) {
                $query->where('id', '!=', $currentBillId);
            })
            ->where(function ($query) use ($challanId) {
                $query->where('export_delivery_challan_id', $challanId)
                    ->orWhereJsonContains('selected_delivery_challan_ids', $challanId);
            })
            ->exists();
    }

    protected function isFormEAvailableForBill(int $formEId, ?int $currentBillId): bool
    {
        return !BillOfLading::query()
            ->when($currentBillId, function ($query) use ($currentBillId) {
                $query->where('id', '!=', $currentBillId);
            })
            ->whereJsonContains('selected_form_e_ids', $formEId)
            ->exists();
    }

    protected function buildDocumentPayload(ExportOrder $exportOrder, Collection $formEs, Collection $deliveryChallans, array $input): array
    {
        $firstDeliveryOrder = $deliveryChallans->flatMap->delivery_order->first();
        $customer = $firstDeliveryOrder?->customer ?? $deliveryChallans->first()?->customer;
        $consignee = $exportOrder->consignee;
        $notify = $consignee ?: $customer;
        $placeOfIssue = $this->resolvePlaceOfIssue($deliveryChallans);
        $goodsSummary = $this->buildGoodsSummaryFromDeliveryChallans($deliveryChallans, $exportOrder);
        $packingTypeSource = $firstDeliveryOrder?->exportOrder?->packing_type ?? $exportOrder->packing_type;
        $packingType = $this->normalizePackingType($packingTypeSource);

        $vesselName = $deliveryChallans->flatMap(function ($challan) {
            return $challan->delivery_challan_data->map(function ($data) {
                return $data->loadingProgramItem?->exportLoadingProgram?->vessel_name;
            });
        })->filter()->unique()->implode(' / ');

        if (!$vesselName) {
            // Check linked Delivery Orders
            $vesselName = $deliveryChallans->flatMap(function ($challan) {
                return $challan->delivery_order->pluck('vessel_name');
            })->filter()->unique()->implode(' / ');
        }

        if (!$vesselName) {
            $vesselName = $exportOrder->vessel_name ?? $exportOrder->carrier_name ?? 'N/A';
        }

        $totalQuantityMt = round((float) ($goodsSummary['totals']['quantity_mt'] ?? 0), 3);
        $totalNetWeightMt = round(((float) ($goodsSummary['totals']['net_weight_kg'] ?? 0)) / 1000, 3);
        $totalGrossWeightMt = round(((float) ($goodsSummary['totals']['gross_weight_kg'] ?? 0)) / 1000, 3);
        $totalContainers = (int) round(collect($goodsSummary['rows'] ?? [])->sum('no_of_containers'));
        $firstRow = $goodsSummary['rows'][0] ?? [];

        $preview = [
            'bill_no' => $input['bill_no'] ?? null,
            'bill_date' => $input['bill_date'] ?? null,
            'carrier_name' => $input['carrier_name'] ?? null,
            'shipped_on_board_date' => $input['shipped_on_board_date'] ?? null,
            'charter_party_dated' => $input['charter_party_dated'] ?? null,
            'cautions_text' => $input['cautions_text'] ?? null,
            'place_of_issue' => $placeOfIssue,
            'shipper_name' => $exportOrder->company?->name,
            'shipper_address' => $exportOrder->company?->address,
            'shipper_phone' => $exportOrder->company?->phone,
            'on_behalf_of' => $customer?->name,
            'consignee_name' => $consignee?->name ?? $customer?->name,
            'consignee_address' => $consignee?->address ?? $customer?->address,
            'consignee_phone' => $consignee?->contact ?? $customer?->phone,
            'consignee_contact_person' => $consignee?->contact_person,
            'notify_name' => $notify?->name,
            'notify_address' => $notify?->address,
            'notify_phone' => $notify?->contact ?? $notify?->phone ?? null,
            'notify_contact_person' => $notify?->contact_person ?? null,
            'vessel_name' => $vesselName,
            'packing_type' => $packingType,
            'packing_type_source' => $packingTypeSource,
            'port_of_loading' => $this->formatPort($exportOrder->portOfLoading),
            'port_of_discharge' => $this->formatPort($exportOrder->portOfDischarge),
            'place_of_delivery' => $this->formatPort($exportOrder->portOfDischarge),
            'product_name' => $exportOrder->visual_name ?: ($exportOrder->product?->name ?: 'N/A'),
            'form_e_no' => $formEs->pluck('form_e_no')->filter()->implode(', '),
            'form_e_date' => $formEs->pluck('form_e_date')->filter()->map(fn($date) => Carbon::parse($date)->format('d.m.Y'))->implode(', '),
            'delivery_challan_no' => $deliveryChallans->pluck('dc_no')->filter()->implode(', '),
            'delivery_order_no' => $deliveryChallans->flatMap->delivery_order->pluck('reference_no')->filter()->unique()->implode(', '),
            'export_order_no' => $exportOrder->voucher_no,
            'quantity_summary' => number_format($totalQuantityMt, 3) . ' MT IN ' . number_format($totalContainers) . ' CONTAINERS',
            'gross_weight_mt' => $totalGrossWeightMt,
            'net_weight_mt' => $totalNetWeightMt,
            'total_containers' => $totalContainers,
            'bag_marking_text' => $firstRow['brand_name'] ?? 'N/A',
            'packing_description' => $firstRow['container_packing_description'] ?? 'N/A',
            'number_of_bags_summary' => $firstRow['container_bag_count_summary'] ?? 'N/A',
            'financial_instrument_no' => $firstDeliveryOrder?->financial_instrument_no ?? 'N/A',
            'empty_bags_note' => $this->buildContainerEmptyBagsNote($goodsSummary['rows'] ?? []),
        ];

        return [$preview, $goodsSummary];
    }

    protected function resolveDocumentPayloadFromBillOfLading(BillOfLading $billOfLading): array
    {
        $preview = $billOfLading->snapshot_data ?? [];
        $goodsSummary = $billOfLading->goods_summary ?? [];

        $hasContainerAwarePayload = !empty($preview['packing_type']) && !empty($preview['place_of_delivery']);
        $hasUsableStoredPayload = !empty($preview) && !empty($goodsSummary) && $hasContainerAwarePayload;

        if ($hasUsableStoredPayload) {
            return [$preview, $goodsSummary];
        }

        $validated = [
            'export_order_id' => $billOfLading->export_order_id,
            'export_form_e_ids' => array_values(array_filter((array) $billOfLading->selected_form_e_ids)),
            'export_delivery_challan_ids' => array_values(array_filter((array) $billOfLading->selected_delivery_challan_ids)),
            'bill_no' => $billOfLading->bill_no,
            'bill_date' => optional($billOfLading->bill_date)->format('Y-m-d'),
            'carrier_name' => $billOfLading->carrier_name,
            'shipped_on_board_date' => optional($billOfLading->shipped_on_board_date)->format('Y-m-d'),
            'charter_party_dated' => $billOfLading->charter_party_dated,
            'cautions_text' => $billOfLading->cautions_text,
        ];

        [$exportOrder, $formEs, $deliveryChallans] = $this->resolveSelections($validated);
        [$preview, $goodsSummary] = $this->buildDocumentPayload($exportOrder, $formEs, $deliveryChallans, $validated);

        return [$preview, $goodsSummary];
    }

    protected function buildGoodsSummaryFromDeliveryChallans(Collection $deliveryChallans, ExportOrder $exportOrder): array
    {
        $productName = $exportOrder->visual_name ?: ($exportOrder->product?->name ?: 'N/A');

        $rows = $deliveryChallans->flatMap(function ($challan) {
            return $challan->delivery_challan_data;
        })->groupBy(function ($item) {
            $packingText = $this->normalizePackingText($item->bag_size);
            return implode('|', [
                (string) ($item->brand_id ?? ''),
                $packingText,
                (string) ($item->bag_type ?? ''),
            ]);
        })->values()->map(function ($items, $index) use ($productName) {
            $first = $items->first();
            $packingText = $this->normalizePackingText($first->bag_size);
            $packingKg = $this->parsePackingKg($packingText);
            $brandName = $items->pluck('brand_id')->map(fn($id) => getBrandById($id)?->name)->filter()->unique()->implode(', ');
            $bagTypeName = $first->bag_type ? bag_type_name($first->bag_type) : 'N/A';
            $bagConditionName = $first->deliveryOrderData?->bagCondition?->name
                ?? optional(getBagConditionById($first->deliveryOrderData?->bag_condition_id))->name
                ?? 'N/A';
            $quantityMt = round((float) $items->sum(function ($item) {
                return (float) ($item->qty ?? 0);
            }), 3);
            $noOfBags = (int) round($items->sum(function ($item) {
                return (float) ($item->no_of_bags ?? 0);
            }));
            $containerCount = (int) round($items->sum(function ($item) {
                return (float) ($item->deliveryOrderData?->no_of_containers ?? 0);
            }));
            if ($containerCount <= 0) {
                $containerCount = (int) round((float) ($first->deliveryOrderData?->no_of_containers ?? 0));
            }
            $netWeightKg = round($quantityMt * 1000, 2);
            $extraBags = (float) $items->sum(function ($item) {
                return $this->getProRatedBagCount($item, 'extra_bags');
            });
            $emptyBags = (float) $items->sum(function ($item) {
                return $this->getProRatedBagCount($item, 'empty_bags');
            });
            $emptyBagWeightGram = (float) ($first->deliveryOrderData->min_weight_empty_bags ?? 0);
            $emptyBagWeightKg = $emptyBagWeightGram / 1000;

            $grossBags = $noOfBags + $extraBags + $emptyBags;
            $grossWeightKg = round($netWeightKg + ($grossBags * $emptyBagWeightKg), 2);
            $containerPackingDescription = 'PACKED IN ';

            if (strtoupper($bagConditionName) !== 'N/A') {
                $containerPackingDescription .= strtoupper($bagConditionName) . ' ';
            }

            $containerPackingDescription .= strtoupper((string) $bagTypeName) . ' BAGS';
            if ($packingText !== '') {
                $containerPackingDescription .= ' OF ' . $packingText . ' KGS NET EACH';
            }
            $containerPackingDescription .= '.';

            return [
                'row_label' => chr(65 + $index),
                'quantity_mt' => $quantityMt,
                'product_name' => $productName,
                'packing_text' => $packingText,
                'packing_kg' => $packingKg,
                'bag_type' => $bagTypeName,
                'brand_name' => $brandName,
                'no_of_bags' => $noOfBags,
                'extra_bags' => round($extraBags, 2),
                'empty_bags' => round($emptyBags, 2),
                'gross_bags' => round($grossBags, 2),
                'net_weight_kg' => $netWeightKg,
                'gross_weight_kg' => $grossWeightKg,
                'no_of_containers' => $containerCount,
                'bag_markings' => trim($brandName . ($packingText ? ' - ' . $packingText : '')),
                'number_of_bags_text' => trim(number_format($noOfBags) . ' BAGS OF ' . $packingText . ' | ' . number_format($quantityMt, 2, '.', '') . ' MT | ' . strtoupper($bagTypeName)),
                'container_packing_description' => $containerPackingDescription,
                'container_bag_count_summary' => number_format($noOfBags) . ' BAGS',
                'description_lines' => [
                    trim(number_format($quantityMt, 2, '.', '') . ' MT ' . $productName),
                    trim('PACKING: ' . $packingText),
                    trim('BAG TYPE: ' . $bagTypeName),
                ],
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => [
                'quantity_mt' => round(collect($rows)->sum('quantity_mt'), 2),
                'net_weight_kg' => round(collect($rows)->sum('net_weight_kg'), 2),
                'gross_weight_kg' => round(collect($rows)->sum('gross_weight_kg'), 2),
                'gross_bags' => (int) round(collect($rows)->sum('gross_bags')),
            ],
        ];
    }

    protected function resolvePlaceOfIssue(Collection $deliveryChallans): string
    {
        $locationIds = collect();
        
        foreach ($deliveryChallans as $challan) {
            // 1. Check Challan location_id
            if ($challan->location_id) {
                $ids = array_values(array_filter(array_map('trim', explode(',', (string) $challan->location_id))));
                $locationIds = $locationIds->concat($ids);
            }
            
            // 2. Check linked Delivery Orders
            foreach ($challan->delivery_order as $deliveryOrder) {
                if ($deliveryOrder->location_id) {
                    $ids = array_values(array_filter(array_map('trim', explode(',', (string) $deliveryOrder->location_id))));
                    $locationIds = $locationIds->concat($ids);
                }
            }
            
            // 3. Check Tickets (Loading Program) associated with the Challan
            foreach ($challan->delivery_challan_data as $data) {
                $ticket = $data->loadingProgramItem;
                if ($ticket && $ticket->exportLoadingProgram && $ticket->exportLoadingProgram->company_locations) {
                    $locationIds = $locationIds->concat((array) $ticket->exportLoadingProgram->company_locations);
                }
            }
        }
        
        $locationIds = $locationIds->filter()->unique()->values();

        if ($locationIds->isEmpty()) {
            return 'N/A';
        }

        return CompanyLocation::whereIn('id', $locationIds)->pluck('name')->implode(', ');
    }

    protected function normalizePackingText($packing): string
    {
        return trim((string) explode(',', (string) $packing)[0]);
    }

    protected function parsePackingKg(string $packingText): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $packingText);
    }

    protected function getProRatedBagCount($deliveryChallanData, string $field): float
    {
        $packingItem = $deliveryChallanData->deliveryOrderData;
        if (!$packingItem) {
            return 0;
        }

        // Get Export Order associated with this packing item through Delivery Order
        $exportOrder = $packingItem->deliveryOrder?->exportOrder;
        
        if ($exportOrder) {
            // Use EO-based ratio for accurate distribution across multiple DOs
            $eoPackingItems = $exportOrder->packingItems;
            $sourceMetricTons = (float) $eoPackingItems->sum('metric_tons');
            $sourceCount = (float) $eoPackingItems->sum($field);
        } else {
            // Fallback to individual packing item ratio
            $sourceMetricTons = (float) ($packingItem->metric_tons ?? 0);
            $sourceCount = (float) ($packingItem->{$field} ?? 0);
        }

        $dispatchMetricTons = (float) ($deliveryChallanData->qty ?? 0);

        if ($sourceMetricTons <= 0 || $dispatchMetricTons <= 0) {
            return 0;
        }

        $ratio = $dispatchMetricTons / $sourceMetricTons;

        return round($sourceCount * $ratio, 2);
    }

    protected function formatPort($port): ?string
    {
        if (!$port) {
            return null;
        }

        return trim($port->name . ($port->country?->name ? ', ' . $port->country->name : ''));
    }

    protected function normalizePackingType(?string $packingType): string
    {
        $value = strtoupper(trim((string) $packingType));

        if (in_array($value, ['IN CONTAINER', 'IN CONATINER'], true)) {
            return 'container';
        }

        return 'bulk';
    }

    protected function buildContainerEmptyBagsNote(array $rows): ?string
    {
        $emptyBags = round(collect($rows)->sum('empty_bags'), 2);

        if ($emptyBags <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format($emptyBags, 2, '.', ''), '0'), '.') . " PCT EMPTY BAGS WITH BUYER'S MARKING";
    }
}
