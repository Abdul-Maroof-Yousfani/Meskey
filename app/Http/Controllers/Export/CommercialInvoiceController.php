<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\BillOfLading;
use App\Models\Export\CommercialInvoice;
use App\Models\Export\ExportDeliveryChallan;
use App\Models\Export\ExportOrder;
use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommercialInvoiceController extends Controller
{
    public function index(): View
    {
        return view('management.export.commercial-invoice.index');
    }

    public function getCommercialInvoiceTable(Request $request): View
    {
        $search = trim((string) $request->get('search'));

        $invoices = CommercialInvoice::with(['exportOrder', 'billOfLading'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('commercial_invoice_no', 'like', "%{$search}%")
                        ->orWhere('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('exportOrder', function ($subQ) use ($search) {
                            $subQ->where('voucher_no', 'like', "%{$search}%")
                                ->orWhere('contract_no', 'like', "%{$search}%");
                        })
                        ->orWhereHas('billOfLading', function ($subQ) use ($search) {
                            $subQ->where('bill_no', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        $invoices->getCollection()->transform(function (CommercialInvoice $invoice) {
            [, $preview] = $this->buildPayloadFromInvoice($invoice);
            $invoice->computed_preview = $preview;

            return $invoice;
        });

        return view('management.export.commercial-invoice.getList', compact('invoices'));
    }

    public function create(): View
    {
        return view('management.export.commercial-invoice.create', [
            'commercialInvoice' => null,
            'exportOrders' => $this->getEligibleExportOrders()->get(),
            'preview' => null,
            'goodsSummary' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCommercialInvoice($request);

        DB::beginTransaction();

        try {
            [$billOfLading, $preview, $goodsSummary] = $this->buildPayloadFromRequest($validated);

            CommercialInvoice::create([
                'export_order_id' => $billOfLading->export_order_id,
                'bill_of_lading_id' => $billOfLading->id,
                'commercial_invoice_no' => $validated['commercial_invoice_no'],
                'invoice_no' => $validated['commercial_invoice_no'],
                'invoice_date' => $validated['invoice_date'] ?? null,
                'created_by' => auth()->user()?->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Commercial Invoice has been created']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id): View
    {
        $commercialInvoice = CommercialInvoice::with(['exportOrder', 'billOfLading'])->findOrFail($id);
        [, $preview, $goodsSummary] = $this->buildPayloadFromInvoice($commercialInvoice);

        return view('management.export.commercial-invoice.show', [
            'commercialInvoice' => $commercialInvoice,
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function edit($id): View
    {
        $commercialInvoice = CommercialInvoice::with(['exportOrder', 'billOfLading'])->findOrFail($id);
        [, $preview, $goodsSummary] = $this->buildPayloadFromInvoice($commercialInvoice);

        return view('management.export.commercial-invoice.edit', [
            'commercialInvoice' => $commercialInvoice,
            'exportOrders' => $this->getEligibleExportOrders()->get(),
            'preview' => $preview,
            'goodsSummary' => $goodsSummary,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $commercialInvoice = CommercialInvoice::findOrFail($id);
        $validated = $this->validateCommercialInvoice($request, $commercialInvoice->id);

        DB::beginTransaction();

        try {
            [$billOfLading, $preview, $goodsSummary] = $this->buildPayloadFromRequest($validated, $commercialInvoice->id);

            $commercialInvoice->update([
                'export_order_id' => $billOfLading->export_order_id,
                'bill_of_lading_id' => $billOfLading->id,
                'commercial_invoice_no' => $validated['commercial_invoice_no'],
                'invoice_no' => $validated['commercial_invoice_no'],
                'invoice_date' => $validated['invoice_date'] ?? null,
            ]);

            DB::commit();

            return response()->json(['message' => 'Commercial Invoice has been updated']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $commercialInvoice = CommercialInvoice::findOrFail($id);
        $commercialInvoice->delete();

        return response()->json(['message' => 'Commercial Invoice has been deleted']);
    }

    public function getNumber(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->get('invoice_date', now()->toDateString()));
        $exportOrder = null;

        if ($request->filled('export_order_id')) {
            $exportOrder = ExportOrder::with('company')->find($request->integer('export_order_id'));
        }

        $companyPrefix = strtoupper(trim((string) ($exportOrder?->company?->prefix ?: 'MFT')));
        $companyPrefix = preg_replace('/[^A-Z0-9]/', '', $companyPrefix) ?: 'MFT';
        $prefix = 'INV/' . $companyPrefix . '/' . $date->format('Y');
        $latest = CommercialInvoice::where('invoice_no', 'like', $prefix . '/%')->latest('id')->first();
        $next = 1;

        if ($latest) {
            $parts = explode('/', (string) $latest->invoice_no);
            $next = ((int) end($parts)) + 1;
        }

        return response()->json([
            'success' => true,
            'commercial_invoice_no' => $prefix . '/' . str_pad((string) $next, 3, '0', STR_PAD_LEFT),
        ]);
    }

    public function getBillOfLadingsByExportOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'current_invoice_id' => ['nullable', 'integer', 'exists:commercial_invoices,id'],
        ]);

        $billOfLadings = BillOfLading::with('exportOrder')
            ->where('export_order_id', $validated['export_order_id'])
            ->latest()
            ->get()
            ->filter(function (BillOfLading $billOfLading) use ($validated) {
                return $this->isBillOfLadingAvailableForInvoice($billOfLading->id, $validated['current_invoice_id'] ?? null);
            })
            ->values()
            ->map(function (BillOfLading $billOfLading) {
                return [
                    'id' => $billOfLading->id,
                    'text' => trim($billOfLading->bill_no . ' - ' . optional($billOfLading->bill_date)->format('d.m.Y')),
                ];
            });

        return response()->json(['success' => true, 'data' => $billOfLadings]);
    }

    public function getRelatedData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'bill_of_lading_id' => ['required', 'exists:bill_of_ladings,id'],
            'commercial_invoice_no' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'current_invoice_id' => ['nullable', 'integer', 'exists:commercial_invoices,id'],
        ]);

        [$billOfLading, $preview, $goodsSummary] = $this->buildPayloadFromRequest($validated, $validated['current_invoice_id'] ?? null);

        return response()->json([
            'success' => true,
            'preview_html' => view('management.export.commercial-invoice.preview', [
                'preview' => $preview,
                'goodsSummary' => $goodsSummary,
            ])->render(),
            'preview' => $preview,
            'goods_summary' => $goodsSummary,
            'bill_of_lading' => [
                'id' => $billOfLading->id,
                'bill_no' => $billOfLading->bill_no,
            ],
        ]);
    }

    protected function validateCommercialInvoice(Request $request, ?int $invoiceId = null): array
    {
        return $request->validate([
            'export_order_id' => ['required', 'exists:export_orders,id'],
            'bill_of_lading_id' => ['required', 'exists:bill_of_ladings,id'],
            'commercial_invoice_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('commercial_invoices', 'commercial_invoice_no')->ignore($invoiceId),
            ],
            'invoice_date' => ['nullable', 'date'],
        ]);
    }

    protected function getEligibleExportOrders()
    {
        return ExportOrder::with(['buyer'])
            ->where('am_approval_status', 'approved')
            ->whereIn('id', BillOfLading::query()->select('export_order_id')->whereNotNull('export_order_id'))
            ->latest();
    }

    protected function buildPayloadFromRequest(array $validated, ?int $currentInvoiceId = null): array
    {
        $billOfLading = BillOfLading::with([
            'exportOrder.company',
            'exportOrder.product',
            'exportOrder.currency',
            'exportOrder.incoterm',
            'exportOrder.modeOfTerm',
            'exportOrder.modeOfTransport',
            'exportOrder.portOfLoading.country',
            'exportOrder.portOfDischarge.country',
            'exportOrder.originCountry',
            'exportOrder.correspondentBank',
            'exportOrder.consignee',
            'exportDeliveryChallan.customer',
        ])->findOrFail($validated['bill_of_lading_id']);

        if ((int) $billOfLading->export_order_id !== (int) $validated['export_order_id']) {
            abort(422, 'Selected Bill of Lading does not belong to the selected Export Order.');
        }

        if (!$this->isBillOfLadingAvailableForInvoice($billOfLading->id, $currentInvoiceId)) {
            abort(422, 'Selected Bill of Lading is already used in another Commercial Invoice.');
        }

        $deliveryChallans = ExportDeliveryChallan::with([
            'customer',
            'delivery_order.customer',
            'delivery_order.exportFormE',
            'delivery_challan_data.deliveryOrderData.brand',
            'delivery_challan_data.deliveryOrderData.bagType',
            'delivery_challan_data.deliveryOrderData.subItems.bagType',
            'delivery_challan_data.deliveryOrderData.subItems.bagSize',
            'delivery_challan_data.product',
        ])
            ->whereIn('id', $billOfLading->selected_delivery_challan_ids ?? [])
            ->get();

        $deliveryOrders = $deliveryChallans->flatMap->delivery_order->unique('id')->values();
        $exportOrder = $billOfLading->exportOrder;

        $goodsSummary = $this->buildGoodsSummary($deliveryChallans, $exportOrder);
        $preview = $this->buildPreviewPayload($billOfLading, $exportOrder, $deliveryOrders, $goodsSummary, $validated);

        return [$billOfLading, $preview, $goodsSummary];
    }

    protected function buildPayloadFromInvoice(CommercialInvoice $commercialInvoice): array
    {
        $input = [
            'export_order_id' => $commercialInvoice->export_order_id,
            'bill_of_lading_id' => $commercialInvoice->bill_of_lading_id,
            'commercial_invoice_no' => $commercialInvoice->invoice_no ?: $commercialInvoice->commercial_invoice_no,
            'invoice_date' => optional($commercialInvoice->invoice_date)->format('Y-m-d'),
        ];

        return $this->buildPayloadFromRequest($input, $commercialInvoice->id);
    }

    protected function isBillOfLadingAvailableForInvoice(int $billOfLadingId, ?int $currentInvoiceId = null): bool
    {
        return !CommercialInvoice::query()
            ->when($currentInvoiceId, function ($query) use ($currentInvoiceId) {
                $query->where('id', '!=', $currentInvoiceId);
            })
            ->where('bill_of_lading_id', $billOfLadingId)
            ->exists();
    }

    protected function buildPreviewPayload(
        BillOfLading $billOfLading,
        ExportOrder $exportOrder,
        Collection $deliveryOrders,
        array $goodsSummary,
        array $input
    ): array {
        $bolSnapshot = $billOfLading->snapshot_data ?? [];
        $customer = $deliveryOrders->first()?->customer ?? $billOfLading->exportDeliveryChallan?->customer;
        
        $consigneeLines = collect([
            $customer?->name ?? null,
            $customer?->address ?? null,
            $customer?->phone ? 'Phone: ' . $customer->phone : null,
        ])->filter()->values();

        $bank = $exportOrder->correspondentBank ?: $exportOrder->customer_bank;
        $paymentTermId = $deliveryOrders->pluck('payment_term_id')->filter()->first();
        $paymentTerm = PaymentTerm::find($paymentTermId);
        
        $company = $exportOrder->company;
        $originName = $exportOrder->originCountry?->name ?? 'Pakistan';
        $visualName = $exportOrder->visual_name ?: ($exportOrder->product?->name ?: 'N/A');
        $originalName = $exportOrder->product?->name ?: $visualName;
        $currencyCode = $exportOrder->currency?->currency_code ?: 'USD';

        $totalAmount = round($goodsSummary['totals']['amount'] ?? 0, 2);
        $amountInWords = null;
        if (function_exists('numberToWords')) {
            $words = numberToWords($totalAmount);
            // Replace 'Rupees' and 'Paise' with correct currency terms if needed
            if ($currencyCode !== 'PKR') {
                $words = str_replace(['Rupees', 'Rupee', 'Paise', 'Paisa'], [$currencyCode, $currencyCode, 'Cents', 'Cent'], $words);
            }
            $amountInWords = $words;
        }

        $quantitySummary = collect($goodsSummary['rows'] ?? [])
            ->map(fn ($row) => number_format((float) ($row['quantity_mt'] ?? 0), 3) . ' MTS')
            ->filter()
            ->implode(' + ');

        return [
            'commercial_invoice_no' => $input['commercial_invoice_no'] ?? null,
            'invoice_date' => $input['invoice_date'] ?? null,
            'invoice_no' => $input['commercial_invoice_no'] ?? null,
            'contract_no' => $exportOrder->contract_no,
            'export_order_no' => $exportOrder->voucher_no,
            'customer_name' => $customer?->name ?? 'N/A',
            'customer_address' => $customer?->address ?? '',
            'customer_phone' => $customer?->phone ?? '',
            'consignee_block' => $consigneeLines->implode("\n"),
            'company_name' => $company?->name,
            'company_address' => $company?->address,
            'company_phone' => $company?->phone,
            'port_of_loading' => $this->formatPort($exportOrder->portOfLoading),
            'port_of_discharge' => $this->formatPort($exportOrder->portOfDischarge),
            'origin_name' => $originName,
            'product_visual_name' => $visualName,
            'product_original_name' => $originalName,
            'contents' => $originalName,
            'quantity_summary' => $quantitySummary,
            'bill_of_lading_no' => $billOfLading->bill_no,
            'bill_of_lading_date' => $billOfLading->bill_date,
            'shipped_on_board_date' => $bolSnapshot['shipped_on_board_date'] ?? $billOfLading->shipped_on_board_date,
            'form_e_no' => $bolSnapshot['form_e_no'] ?? 'N/A',
            'form_e_date' => $bolSnapshot['form_e_date'] ?? null,
            'delivery_challan_no' => $bolSnapshot['delivery_challan_no'] ?? 'N/A',
            'delivery_order_no' => $bolSnapshot['delivery_order_no'] ?? 'N/A',
            'vessel_name' => $bolSnapshot['vessel_name'] ?? $exportOrder->vessel_name,
            'payment_terms' => $paymentTerm?->title ?: ($exportOrder->partial_payment ?: 'As per contract'),
            'incoterm' => $exportOrder->incoterm?->name ?: ($exportOrder->modeOfTerm?->name ?? 'N/A'),
            'mode_of_term' => $exportOrder->modeOfTerm?->name,
            'mode_of_transport' => $exportOrder->modeOfTransport?->name,
            'currency_code' => $currencyCode,
            'currency_rate' => $exportOrder->currency_rate ?: ($exportOrder->currency?->rate ?? 1.0),
            'bank_details' => [
                'account_title' => $bank?->account_title ?? null,
                'bank_name' => $bank?->bank_name ?? null,
                'bank_address' => $bank?->bank_address ?? ($bank?->branch_name ?? null),
                'swift_code' => $bank?->swift_code ?? ($bank?->branch_code ?? null),
                'iban' => $bank?->iban ?? null,
                'account_no' => $bank?->account_no ?? ($bank?->account_number ?? null),
            ],
            'gross_weight_mt' => round(($goodsSummary['totals']['gross_weight_kg'] ?? 0) / 1000, 3),
            'net_weight_mt' => round(($goodsSummary['totals']['net_weight_kg'] ?? 0) / 1000, 3),
            'total_amount' => $totalAmount,
            'amount_in_words' => $amountInWords,
            'selected_bol_summary' => trim(($billOfLading->bill_no ?: 'N/A') . ' / ' . ($bolSnapshot['delivery_challan_no'] ?? 'N/A')),
        ];
    }

    protected function buildGoodsSummary(Collection $deliveryChallans, ExportOrder $exportOrder): array
    {
        $visualName = $exportOrder->visual_name ?: ($exportOrder->product?->name ?: 'N/A');
        $originalName = $exportOrder->product?->name ?: $visualName;
        $rows = $deliveryChallans->flatMap(function ($challan) {
            return $challan->delivery_challan_data;
        })->groupBy(function ($item) {
            $packingText = $this->normalizePackingText($item->bag_size ?? $item->deliveryOrderData?->bag_size);
            $rate = number_format((float) ($item->rate ?? 0), 4, '.', '');

            return implode('|', [
                (string) ($item->brand_id ?? $item->deliveryOrderData?->brand_id ?? ''),
                $packingText,
                (string) ($item->bag_type ?? $item->deliveryOrderData?->bag_type_id ?? ''),
                $rate,
            ]);
        })->values()->map(function ($items) use ($visualName, $originalName, $exportOrder) {
            $first = $items->first();
            $deliveryOrderData = $first->deliveryOrderData;
            $packingText = $this->normalizePackingText($first->bag_size ?? $deliveryOrderData?->bag_size);
            $packingKg = $this->parsePackingKg($packingText);
            $brandId = $first->brand_id ?? $deliveryOrderData?->brand_id;
            $brandName = $brandId ? (getBrandById($brandId)?->name ?? 'N/A') : 'N/A';
            $bagTypeCode = $first->bag_type ?? $deliveryOrderData?->bag_type_id;
            $bagTypeName = $bagTypeCode ? bag_type_name($bagTypeCode) : 'N/A';
            $quantityMt = round((float) $items->sum('qty'), 3);
            $noOfBags = (int) round($items->sum(function ($item) {
                return (float) ($item->no_of_bags ?? 0);
            }));
            $ratePerTon = round((float) ($first->rate ?? 0), 2);
            $amount = round((float) $items->sum(function ($item) {
                return ((float) ($item->qty ?? 0)) * ((float) ($item->rate ?? 0));
            }), 2);
            $netWeightKg = round($quantityMt * 1000, 2);
            $extraBags = (float) $items->sum(function ($item) {
                return $this->getProRatedBagCount($item, 'extra_bags');
            });
            $emptyBags = (float) $items->sum(function ($item) {
                return $this->getProRatedBagCount($item, 'empty_bags');
            });
            $emptyBagWeightGram = (float) ($deliveryOrderData->min_weight_empty_bags ?? 0);
            $emptyBagWeightKg = $emptyBagWeightGram / 1000;
            $grossBags = $noOfBags + $extraBags + $emptyBags;
            $grossWeightKg = round($netWeightKg + ($grossBags * $emptyBagWeightKg), 2);
            $masterPackingText = $this->resolveMasterPackingTextFromDo($items);

            $descriptionLines = collect([
                'QTY: ' . number_format($quantityMt, 3) . ' MTS',
                strtoupper($visualName),
                'PACKING: ' . $packingText . ' KG ' . strtoupper((string) $bagTypeName),
                'STUFFED IN MASTER PACKING: ' . $masterPackingText,
            ])->filter()->values()->all();

            return [
                'brand_name' => $brandName,
                'product_visual_name' => $visualName,
                'product_original_name' => $originalName,
                'packing_text' => $packingText,
                'packing_kg' => $packingKg,
                'bag_type' => $bagTypeName,
                'quantity_mt' => $quantityMt,
                'no_of_bags' => $noOfBags,
                'rate_per_ton' => $ratePerTon,
                'amount' => $amount,
                'net_weight_kg' => $netWeightKg,
                'gross_weight_kg' => $grossWeightKg,
                'gross_bags' => round($grossBags, 2),
                'master_packing_text' => $masterPackingText,
                'description_lines' => $descriptionLines,
                'marks_numbers' => strtoupper($brandName),
                'unit_price_lines' => [
                    $this->formatMoneyLine($ratePerTon),
                    'PER M.TON ' . strtoupper((string) ($exportOrder->incoterm?->name ?? '')),
                    strtoupper((string) ($exportOrder->portOfLoading?->name ?? '')) . ', ' . strtoupper((string) ($exportOrder->originCountry?->name ?? 'PAKISTAN')),
                ],
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => [
                'quantity_mt' => round(collect($rows)->sum('quantity_mt'), 3),
                'net_weight_kg' => round(collect($rows)->sum('net_weight_kg'), 2),
                'gross_weight_kg' => round(collect($rows)->sum('gross_weight_kg'), 2),
                'gross_bags' => round(collect($rows)->sum('gross_bags'), 2),
                'amount' => round(collect($rows)->sum('amount'), 2),
            ],
        ];
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
        $deliveryOrderData = $deliveryChallanData->deliveryOrderData;
        if (!$deliveryOrderData) {
            return 0;
        }

        $sourceCount = (float) ($deliveryOrderData->{$field} ?? 0);
        if ($sourceCount <= 0) {
            return 0;
        }

        $sourceMetricTons = (float) ($deliveryOrderData->metric_tons ?? 0);
        $dispatchMetricTons = (float) ($deliveryChallanData->qty ?? 0);

        if ($sourceMetricTons <= 0 || $dispatchMetricTons <= 0) {
            return 0;
        }

        $ratio = min(max($dispatchMetricTons / $sourceMetricTons, 0), 1);

        return round($sourceCount * $ratio, 2);
    }

    protected function formatPort($port): ?string
    {
        if (!$port) {
            return null;
        }

        return trim($port->name . ($port->country?->name ? ', ' . $port->country->name : ''));
    }

    protected function resolveMasterPackingTextFromDo(Collection $items): string
    {
        $subItems = $items->map(function ($item) {
            return $item->deliveryOrderData?->subItems ?? collect();
        })->flatten(1)->filter();

        if ($subItems->isEmpty()) {
            return 'N/A';
        }

        return $subItems->map(function ($subItem) {
            $size = trim((string) ($subItem->bagSize?->size ?? $subItem->bag_size ?? $subItem->bag_size_id ?? ''));
            $type = strtoupper((string) ($subItem->bagType?->name ?? ''));

            return trim($size . ' ' . $type);
        })->filter()->unique()->implode(' / ');
    }

    protected function formatMoneyLine(float $value): string
    {
        return number_format($value, 2);
    }
}
