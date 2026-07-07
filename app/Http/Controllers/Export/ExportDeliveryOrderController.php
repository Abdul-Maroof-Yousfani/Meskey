<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder as DeliveryOrder;
use App\Models\Export\ExportDeliveryOrderPackingItem;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrder;
use App\Models\Master\Customer;
use App\Models\BagCondition;
use App\Models\BagType;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\Stitching;
use App\Models\Master\CompanyLocation;
use App\Models\Master\InspectionCompany;
use App\Models\Master\FumigationCompany;
use App\Models\Master\ClearingAgent;
use App\Models\Master\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class ExportDeliveryOrderController extends Controller
{
    public function index(Request $request): View
    {
        $delivery_orders = DeliveryOrder::orderBy('id', 'ASC')->paginate(0);
        return view('management.export.delivery-order.index', compact('delivery_orders'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportDeliveryOrderTable(Request $request)
    {
        $delivery_orders = DeliveryOrder::with(['exportOrder.packingItems', 'customer', 'exportFormE', 'exportPackingItems'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                    $sq->where('voucher_no', 'like', $searchTerm)
                        ->orWhere('contract_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.delivery-order.getList', compact('delivery_orders'));
    }

    public function create(): View
    {
        $buyers = Customer::get();

        // Filter only Export Orders that have remaining capacity (Total MT - Consumed MT > 0)
        $export_orders = ExportOrder::with(['packingItems', 'deliveryOrders.exportPackingItems'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()
            ->get()
            ->filter(function ($eo) {
                $totalMt = (float) $eo->packingItems->sum('metric_tons');
                $consumedMt = (float) $eo->deliveryOrders->sum(function ($do) {
                    return $do->exportPackingItems->sum('metric_tons');
                });
                return ($totalMt - $consumedMt) > 0.001;
            });

        $products = collect();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = collect();
        $brands = Brands::all();
        $bagColors = Color::all();
        $users = collect();
        $banks = collect();
        $brokers = collect();
        $incoterms = collect();
        $modeofterms = collect();
        $modeoftransport = collect();
        $countries = collect();
        $ports = collect();
        $hscodes = collect();
        $currencies = collect();
        $exportSodas = collect();
        $quotations = collect();

        // Needed for packing items autofill
        $companyLocations = CompanyLocation::all();
        $bagConditions = BagCondition::all();
        $stitchings = Stitching::all();
        $inspectionCompanies = InspectionCompany::all();
        $transporters = Transporter::all();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $clearingAgents = ClearingAgent::where('status', 'active')->get();

        return view('management.export.delivery-order.create', compact(
            'buyers',
            'export_orders',
            'products',
            'bagTypes',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
            'exportSodas',
            'quotations',
            'companyLocations',
            'bagConditions',
            'stitchings',
            'inspectionCompanies',
            'transporters',
            'fumigationCompanies',
            'clearingAgents'
        ));
    }

    public function getExportOrderDetails($id)
    {
        $exportOrder = ExportOrder::where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->with([
            'product',
            'specifications.productSlabType',
            'packingItems.subItems.bagType',
            'packingItems.subItems.bagSize',
            'packingItems.bagType',
            'packingItems.bagPacking',
            'packingItems.brand',
            'packingItems.bagColor',
            'broker',
            'currency',
            'incoterm',
            'originCountry',
            'portOfDischarge',
            'portOfLoading',
            'hsCode',
            'modeOfTerm',
            'modeOfTransport',
            'exportSoda.product',
            'quotation.product'
        ])->findOrFail($id);

        // Get Job Orders directly from export_order_id
        $jobOrders = \App\Models\Production\JobOrder\JobOrder::where('export_order_id', $id)->pluck('job_order_no')->filter()->values()->toArray();

        $fumigation_by = is_string($exportOrder->fumigation_by) ? json_decode($exportOrder->fumigation_by, true) : (is_array($exportOrder->fumigation_by) ? $exportOrder->fumigation_by : []);
        $inspection_by = is_string($exportOrder->inspection_by) ? json_decode($exportOrder->inspection_by, true) : (is_array($exportOrder->inspection_by) ? $exportOrder->inspection_by : []);

        $deliveryOrders = DeliveryOrder::with('exportPackingItems')->where('export_order_id', $id)->get();
        $totalEoMt = $exportOrder->packingItems->sum('metric_tons');
        $consumedMt = $deliveryOrders->sum(function ($do) {
            return $do->exportPackingItems->sum('metric_tons');
        });

        $remainingMt = max(0, $totalEoMt - $consumedMt);
        $tempConsumed = $consumedMt;

        $packingItems = $exportOrder->packingItems->sortBy('id')->map(function ($item) use (&$tempConsumed) {
            $originalMt = (float) $item->metric_tons;
            $originalBags = (int) $item->no_of_bags;

            $consumedFromThis = min($originalMt, $tempConsumed);
            $tempConsumed -= $consumedFromThis;

            $remainingMtInItem = max(0, $originalMt - $consumedFromThis);
            $remainingBagsInItem = $originalMt > 0 ? round(($remainingMtInItem / $originalMt) * $originalBags) : 0;

            $subItems = $item->subItems->sortBy('id')->map(function ($sub) use ($originalMt, $remainingMtInItem) {
                $subOriginalBags = (int) $sub->no_of_bags;
                $subRemainingBags = $originalMt > 0 ? round(($remainingMtInItem / $originalMt) * $subOriginalBags) : 0;

                return [
                    'bag_product_id' => $sub->bag_type_id,
                    'bag_type_name' => $sub->bagType->name ?? '',
                    'bag_size_id' => $sub->bag_size_id,
                    'bag_size_name' => $sub->bagSize->name ?? ($sub->bag_size_id ?? ''),
                    'stitching_id' => $sub->stitching_id,
                    'bag_color_id' => $sub->bag_color_id,
                    'brand_id' => $sub->brand_id,
                    'thread_color_id' => $sub->thread_color_id,
                    'no_of_primary_bags' => $sub->no_of_primary_bags,
                    'no_of_bags' => $subRemainingBags,
                    'empty_bags' => $sub->empty_bags,
                    'extra_bags' => $sub->extra_bags,
                    'extra_bags_percentage' => ($sub->no_of_bags > 0 && $sub->extra_bags > 0) ? round(($sub->extra_bags / $sub->no_of_bags) * 100, 2) : 0,
                    'empty_bag_weight' => $sub->empty_bag_weight,
                    'total_bags' => $subRemainingBags + ($sub->extra_bags ?? 0) + ($sub->empty_bags ?? 0),
                ];
            })->values();

            return [
                'brand_id' => $item->brand_id,
                'bag_product_id' => $item->bag_type_id,
                'bag_type_name' => $item->bagType->name ?? '',
                'bag_condition_id' => $item->bag_condition_id,
                'bag_color_id' => $item->bag_color_id,
                'thread_color_id' => $item->thread_color_id,
                'stitching_id' => $item->stitching_id,
                'bag_size' => $item->bag_size,
                'no_of_bags' => $remainingBagsInItem,
                'extra_bags' => $item->extra_bags,
                'extra_bags_percentage' => $item->extra_bags_percentage ?? (($item->no_of_bags > 0 && $item->extra_bags > 0) ? round(($item->extra_bags / $item->no_of_bags) * 100, 2) : 0),
                'empty_bags' => $item->empty_bags,
                'total_bags' => $remainingBagsInItem + ($item->extra_bags ?? 0) + ($item->empty_bags ?? 0),
                'total_kgs' => $remainingMtInItem * 1000,
                'metric_tons' => $remainingMtInItem,
                'stuffing_in_container' => $item->stuffing_in_container,
                'no_of_containers' => $item->no_of_containers,
                'min_weight_empty_bags' => $item->min_weight_empty_bags,
                'fumigation_company_id' => $item->fumigation_company_id,
                'fumigation' => $item->fumigation_company_id ? 'Yes' : 'No',
                'phyto_certificate' => $item->fumigation_company_id ?? [], // Same as fumigation, editable
                'inspection_company' => collect($item->inspection_by ?? [])->map(function($id) {
                    $company = InspectionCompany::find($id);
                    return $company ? $company->name : null;
                })->filter()->implode(', '),
                'sub_items' => $subItems,
            ];
        })->values();

        // Fetch Logistics Transporters
        $logistics = \App\Models\Sales\Logistics::with('items.transporter')->where('export_order_id', $id)->get();
        $logisticsTransporters = collect();
        foreach ($logistics as $logistic) {
            foreach ($logistic->items as $item) {
                if ($item->transporter) {
                    $logisticsTransporters->push([
                        'id' => $item->transporter->id,
                        'name' => $item->transporter->name ?? $item->transporter->company_name
                    ]);
                }
            }
        }
        $logisticsTransporters = $logisticsTransporters->unique('id')->values();

        // Fetch C-Freight details
        $cFreight = \App\Models\Export\CFreight::with(['rates' => function($q) {
            $q->where('is_approved', 1);
        }])->where('export_order_id', $id)->first();

        $cFreightAutofill = [
            'vessel_name' => $cFreight ? $cFreight->vessel_name : '',
            'vessel_eta' => $cFreight ? $cFreight->eta : '',
            'vessel_etd' => $cFreight ? $cFreight->etd : '',
            'shipping_line' => $cFreight ? $cFreight->shipping_line : '',
            'freight_amount' => ($cFreight && $cFreight->rates->isNotEmpty()) ? $cFreight->rates->first()->price : '',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'export_order' => $exportOrder,
                'packing_items_autofill' => $packingItems,
                'total_eo_mt' => round($totalEoMt, 3),
                'consumed_mt' => round($consumedMt, 3),
                'remaining_mt' => round($remainingMt, 3),
                'autofill' => array_merge([
                    'job_orders' => $jobOrders,
                    'logistics_transporters' => $logisticsTransporters,
                    'inspection_by' => $inspection_by,
                    'fumigation_by' => $fumigation_by,
                    'phyto_certificate' => $exportOrder->packingItems->pluck('phyto_certificate')->flatten()->unique()->values(),
                    'carton_supplier' => $exportOrder->packingItems->pluck('carton_supplier')->filter()->first(),
                    'fumigation_tablets' => $exportOrder->packingItems->pluck('fumigation_tablets')->filter()->first(),
                    'fumigation_ref_no' => $exportOrder->packingItems->pluck('fumigation_ref_no')->filter()->first(),
                ], $cFreightAutofill)
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'export_order_id' => 'required|exists:export_orders,id',
            'buyer_id' => 'required|exists:customers,id',
            'export_form_e_id' => 'required|exists:export_form_es,id',
            'ref_no' => 'required',
            'packing_items' => 'required|array',
            'financial_instrument_no' => 'required',
            'vessel_name' => 'required',
            'vessel_etd' => 'required|date',
            'vessel_eta' => 'required|date|after_or_equal:vessel_etd',
            'loading_date' => 'required|date',
            'estimated_payment_date' => 'required|date',
            'freight_amount' => 'required|string',
            'transporter_id' => 'required|array',
            'c_agent' => 'required',
            'shipping_line' => 'required',
            'empty_container_pickup' => 'required',
            'locations' => 'required|array|min:1',
            'locations.0.location_id' => 'required',
        ], [
            'buyer_id.required' => 'Customer is required.',
            'vessel_eta.after_or_equal' => 'Vessel ETA must be after or equal to Vessel ETD.',
            'locations.required' => 'At least one location is required.',
            'locations.0.location_id.required' => 'Please select a location.',
        ]);

        if ($validator->fails()) {
            $allErrors = $validator->errors()->messages();
            $uniqueErrors = [];
            $seenMessages = [];

            foreach ($allErrors as $field => $messages) {
                foreach ($messages as $message) {
                    if (!in_array($message, $seenMessages)) {
                        $uniqueErrors[$field] = [$message];
                        $seenMessages[] = $message;
                    }
                }
            }

            return response()->json([
                'status' => 422,
                'errors' => $uniqueErrors
            ], 422);
        }

        DB::beginTransaction();

        $formE = ExportFormE::findOrFail($request->export_form_e_id);
        $totalAllowedMt = (float) $formE->input_quantity;
        $alreadyConsumedMt = DeliveryOrder::with(['exportPackingItems'])
            ->where('export_form_e_id', $request->export_form_e_id)
            ->get()
            ->sum(function ($do) {
                return $do->exportPackingItems->sum('metric_tons');
            });

        $currentRequestMt = 0;
        foreach ($request->packing_items as $index => $itemData) {
            // ONLY skip if bag_product_id is missing (dummy row pattern)
            if (empty($itemData['bag_product_id'])) {
                continue;
            }
            $currentRequestMt += floatval($itemData['metric_tons'] ?? 0);
        }

        if (($alreadyConsumedMt + $currentRequestMt) > ($totalAllowedMt + 0.001)) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => "Total Metric Tons ($currentRequestMt) exceeds the remaining capacity of Export Form-E (" . round($totalAllowedMt - $alreadyConsumedMt, 2) . " MT)."
            ], 422);
        }

        $deliveryOrder = \Illuminate\Support\Facades\Cache::lock('export_do_generation', 10)->block(5, function () use ($request) {
            // Re-generate reference_no server-side to ensure uniqueness
            $datePart = Carbon::parse($request->dispatch_date)->format('Y-m-d');
            $prefix = 'DO-' . $datePart;

            $latestContract = DeliveryOrder::withoutGlobalScopes()
                ->where('reference_no', 'like', "$prefix-%")
                ->orderBy('reference_no', 'desc')
                ->first();

            if ($latestContract) {
                $parts = explode('-', $latestContract->reference_no);
                $lastNumber = (int) end($parts);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $reference_no = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            $deliveryOrder = DeliveryOrder::create([
                'type' => 'export_order',
                'export_order_id' => $request->export_order_id,
                'customer_id' => $request->buyer_id,
                'export_form_e_id' => $request->export_form_e_id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user() ? auth()->user()->id : null,
                'reference_no' => $reference_no,
                'ref_no' => $request->ref_no,
                'location_id' => null,
                'arrival_location_id' => null,
                'sub_arrival_location_id' => null,
                'am_approval_status' => 'pending',

                // New fields
                'financial_instrument_no' => $request->financial_instrument_no,
                'job_order_no' => $request->job_order_no,
                'vessel_name' => $request->vessel_name,
                'vessel_etd' => $request->vessel_etd,
                'vessel_eta' => $request->vessel_eta,
                'loading_date' => $request->loading_date,
                'estimated_payment_date' => $request->estimated_payment_date,
                'freight_amount' => $request->freight_amount ?? '',
                'transporter_id' => $request->has('transporter_id') ? json_encode($request->transporter_id) : null,
                'c_agent' => $request->c_agent,
                'shipping_line' => $request->shipping_line,
                'empty_container_pickup' => $request->empty_container_pickup,
                'fumigation_by' => $request->has('fumigation_by_hidden') ? $request->fumigation_by_hidden : ($request->has('fumigation_by') ? json_encode($request->fumigation_by) : null),
                'inspection_by' => $request->has('inspection_by_hidden') ? $request->inspection_by_hidden : ($request->has('inspection_by') ? json_encode($request->inspection_by) : null),
                'phyto_certificate' => $request->has('phyto_certificate') ? json_encode($request->phyto_certificate) : null,
                'carton_supplier' => $request->carton_supplier,
                'fumigation_tablets' => $request->fumigation_tablets,
                'fumigation_ref_no' => $request->fumigation_ref_no,
            ]);

            // Save multiple locations
            if ($request->has('locations') && is_array($request->locations)) {
                foreach ($request->locations as $locData) {
                    if (empty($locData['location_id'])) continue;
                    $deliveryOrder->locations()->create([
                        'company_location_id' => $locData['location_id'],
                        'arrival_location_ids' => isset($locData['arrival_ids']) ? implode(',', (array) $locData['arrival_ids']) : null,
                        'sub_arrival_location_ids' => isset($locData['storage_ids']) ? implode(',', (array) $locData['storage_ids']) : null,
                    ]);
                }
            }

            return $deliveryOrder;
        });

        foreach ($request->packing_items as $index => $itemData) {
            // Skip dummy row only
            if (empty($itemData['bag_product_id'])) {
                continue;
            }
            $packingItem = $deliveryOrder->exportPackingItems()->create([
                'company_location_id' => $itemData['company_location_id'] ?? null,
                'bag_type_id' => $itemData['bag_product_id'] ?? null,
                'bag_condition_id' => $itemData['bag_condition_id'] ?? null,
                'bag_size' => $itemData['bag_size'] ?? 0,
                'no_of_bags' => $itemData['no_of_bags'] ?? 0,
                'extra_bags' => $itemData['extra_bags'] ?? 0,
                'extra_bags_percentage' => $itemData['extra_bags_percentage'] ?? 0,
                'empty_bags' => $itemData['empty_bags'] ?? 0,
                'total_bags' => $itemData['total_bags'] ?? 0,
                'total_kgs' => $itemData['total_kgs'] ?? 0,
                'metric_tons' => $itemData['metric_tons'] ?? 0,
                'stuffing_in_container' => $itemData['stuffing_in_container'] ?? 0,
                'no_of_containers' => $itemData['no_of_containers'] ?? 0,
                'brand_id' => $itemData['brand_id'] ?? null,
                'bag_color_id' => $itemData['bag_color_id'] ?? null,
                'thread_color_id' => $itemData['thread_color_id'] ?? null,
                'stitching_id' => $itemData['stitching_id'] ?? null,
                'min_weight_empty_bags' => $itemData['min_weight_empty_bags'] ?? 0,

            ]);

            if (isset($itemData['sub_items']) && is_array($itemData['sub_items'])) {
                foreach ($itemData['sub_items'] as $subItemData) {
                    $packingItem->subItems()->create([
                        'bag_type_id' => $subItemData['bag_product_id'] ?? null,
                        'bag_size_id' => $subItemData['bag_size_id'] ?? null,
                        'no_of_primary_bags' => $subItemData['no_of_primary_bags'] ?? 0,
                        'no_of_bags' => $subItemData['no_of_bags'] ?? 0,
                        'empty_bags' => $subItemData['empty_bags'] ?? 0,
                        'extra_bags' => $subItemData['extra_bags'] ?? 0,
                        'empty_bag_weight' => $subItemData['empty_bag_weight'] ?? 0,
                        'total_bags' => $subItemData['total_bags'] ?? 0,
                        'total_kgs' => $subItemData['total_kgs'] ?? 0,
                        'stitching_id' => $subItemData['stitching_id'] ?? null,
                        'bag_color_id' => $subItemData['bag_color_id'] ?? null,
                        'brand_id' => $subItemData['brand_id'] ?? null,
                        'thread_color_id' => $subItemData['thread_color_id'] ?? null,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => 'Export Delivery Order created successfully',
            'data' => $deliveryOrder,
        ], 201);
    }

    public function show($id): View
    {
        $deliveryOrder = DeliveryOrder::with([
            'customer',
            'exportFormE',
            'exportOrder.quotation.product',
            'exportOrder.exportSoda.product',
            'exportOrder.product',
            'exportOrder.specifications.productSlabType',
            'exportOrder.incoterm',
            'exportOrder.modeOfTerm',
            'exportOrder.modeOfTransport',
            'exportOrder.originCountry',
            'exportOrder.portOfDischarge',
            'exportOrder.portOfLoading',
            'exportOrder.hsCode',
            'exportOrder.currency',
            'exportOrder.packingItems',
            'exportPackingItems.subItems.bagType',
            'exportPackingItems.subItems.bagSize',
            'exportPackingItems.brand',
            'exportPackingItems.bagType',
            'exportPackingItems.bagCondition',
            'exportPackingItems.bagColor',
            'exportPackingItems.threadColor',
            'exportPackingItems.stitching',
            'locations.companyLocation',
            'clearingAgent',
        ])->findOrFail($id);

        $exportOrderData = $deliveryOrder->exportOrder;

        // Calculate quantity variables for the view (Form-E centric)
        $totalAllowedMt = (float) ($deliveryOrder->exportFormE->input_quantity ?? 0);
        $alreadyConsumedMt = (float) DeliveryOrder::with(['exportPackingItems'])
            ->where('export_form_e_id', $deliveryOrder->export_form_e_id)
            ->where('id', '!=', $id)
            ->get()
            ->sum(function ($do) {
                return $do->exportPackingItems->sum('metric_tons');
            });
        $currentRequestMt = (float) $deliveryOrder->exportPackingItems->sum('metric_tons');
        $remainingMt = max(0, $totalAllowedMt - $alreadyConsumedMt - $currentRequestMt);

        $brands = Brands::all();
        $bagColors = Color::all();
        $threadColors = Color::all();
        $stitchings = Stitching::all();
        $inspectionCompanies = InspectionCompany::all();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $transporters = Transporter::all();

        return view('management.export.delivery-order.show', compact(
            'deliveryOrder',
            'exportOrderData',
            'brands',
            'bagColors',
            'threadColors',
            'stitchings',
            'fumigationCompanies',
            'inspectionCompanies',
            'bagTypes',
            'bagConditions',
            'totalAllowedMt',
            'alreadyConsumedMt',
            'remainingMt',
            'currentRequestMt',
            'transporters'
        ));
    }

    public function edit($id): View
    {
        $deliveryOrder = DeliveryOrder::with([
            'exportPackingItems.subItems.bagSize',
            'exportOrder.quotation.product',
            'exportOrder.exportSoda.product',
            'exportOrder.product',
            'exportOrder.specifications.productSlabType',
            'exportOrder.incoterm',
            'exportOrder.modeOfTerm',
            'exportOrder.modeOfTransport',
            'exportOrder.originCountry',
            'exportOrder.portOfDischarge',
            'exportOrder.portOfLoading',
            'exportOrder.hsCode',
            'exportOrder.currency',
            'exportOrder.packingItems',
            'locations.companyLocation'
        ])->findOrFail($id);
        $buyers = Customer::get();
        $export_orders = ExportOrder::where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()->get();

        // Calculate quantity variables for the view (Form-E centric)
        $totalAllowedMt = (float) ($deliveryOrder->exportFormE->input_quantity ?? 0);
        $alreadyConsumedMt = (float) DeliveryOrder::with(['exportPackingItems'])
            ->where('export_form_e_id', $deliveryOrder->export_form_e_id)
            ->where('id', '!=', $id)
            ->get()
            ->sum(function ($do) {
                return $do->exportPackingItems->sum('metric_tons');
            });
        $currentRequestMt = (float) $deliveryOrder->exportPackingItems->sum('metric_tons');
        $remainingMt = max(0, $totalAllowedMt - $alreadyConsumedMt - $currentRequestMt);

        $products = collect();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = collect();
        $brands = Brands::all();
        $bagColors = Color::all();
        $users = collect();
        $banks = collect();
        $brokers = collect();
        $incoterms = collect();
        $modeofterms = collect();
        $modeoftransport = collect();
        $countries = collect();
        $ports = collect();
        $hscodes = collect();
        $currencies = collect();
        $exportSodas = collect();
        $quotations = collect();
        $companyLocations = CompanyLocation::all();
        $bagConditions = BagCondition::all();
        $stitchings = Stitching::all();
        $inspectionCompanies = InspectionCompany::all();
        $transporters = Transporter::all();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $clearingAgents = ClearingAgent::where('status', 'active')->get();

        return view('management.export.delivery-order.edit', compact(
            'deliveryOrder',
            'buyers',
            'export_orders',
            'products',
            'bagTypes',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
            'exportSodas',
            'quotations',
            'companyLocations',
            'bagConditions',
            'stitchings',
            'inspectionCompanies',
            'transporters',
            'fumigationCompanies',
            'totalAllowedMt',
            'alreadyConsumedMt',
            'remainingMt',
            'currentRequestMt',
            'clearingAgents'
        ));
    }

    public function update(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'export_form_e_id' => 'required|exists:export_form_es,id',
            'ref_no' => 'required',
            'packing_items' => 'required|array',
            'financial_instrument_no' => 'required',
            'vessel_name' => 'required',
            'vessel_etd' => 'required|date',
            'vessel_eta' => 'required|date|after_or_equal:vessel_etd',
            'loading_date' => 'required|date',
            'estimated_payment_date' => 'required|date',
            'freight_amount' => 'required|string',
            'transporter_id' => 'required|array',
            'c_agent' => 'required',
            'shipping_line' => 'required',
            'empty_container_pickup' => 'required',
            'locations' => 'required|array|min:1',
            'locations.0.location_id' => 'required',
        ], [
            'vessel_eta.after_or_equal' => 'Vessel ETA must be after or equal to Vessel ETD.',
            'locations.required' => 'At least one location is required.',
            'locations.0.location_id.required' => 'Please select a location.',
        ]);

        if ($validator->fails()) {
            $allErrors = $validator->errors()->messages();
            $uniqueErrors = [];
            $seenMessages = [];

            foreach ($allErrors as $field => $messages) {
                foreach ($messages as $message) {
                    if (!in_array($message, $seenMessages)) {
                        $uniqueErrors[$field] = [$message];
                        $seenMessages[] = $message;
                    }
                }
            }

            return response()->json([
                'status' => 422,
                'errors' => $uniqueErrors
            ], 422);
        }

        DB::beginTransaction();

        try {
            $deliveryOrder = DeliveryOrder::with(['exportPackingItems.subItems'])
                ->lockForUpdate()
                ->find($id);

            if (!$deliveryOrder) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Order already deleted or not found.',
                ], 404);
            }

            if (
                $deliveryOrder->am_approval_status === "approved" ||
                $deliveryOrder->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Order has been approved/rejected and cannot be updated.',
                ], 400);
            }

            $exportOrderId = $deliveryOrder->export_order_id;
            $formE = ExportFormE::findOrFail($request->export_form_e_id);
            $totalAllowedMt = (float) $formE->input_quantity;
            $alreadyConsumedMt = DeliveryOrder::with(['exportPackingItems'])
                ->where('export_form_e_id', $request->export_form_e_id)
                ->where('id', '!=', $id)
                ->get()
                ->sum(function ($do) {
                    return $do->exportPackingItems->sum('metric_tons');
                });

            $currentRequestMt = 0;

            foreach ($request->packing_items as $index => $itemData) {
                if (empty($itemData['bag_product_id'])) {
                    continue;
                }
                $currentRequestMt += floatval($itemData['metric_tons'] ?? 0);
            }

            if (($alreadyConsumedMt + $currentRequestMt) > ($totalAllowedMt + 0.001)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Total Metric Tons ($currentRequestMt) exceeds the remaining capacity of Export Form-E (" . round($totalAllowedMt - $alreadyConsumedMt, 2) . " MT)."
                ], 422);
            }

            $deliveryOrder->update([
                'remarks' => $request->remarks,
                'export_form_e_id' => $request->export_form_e_id,
                'reference_no' => $request->reference_no ?? $deliveryOrder->reference_no,
                'ref_no' => $request->ref_no,
                'location_id' => null,
                'arrival_location_id' => null,
                'sub_arrival_location_id' => null,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,

                // New fields
                'financial_instrument_no' => $request->financial_instrument_no,
                'job_order_no' => $request->job_order_no,
                'vessel_name' => $request->vessel_name,
                'vessel_etd' => $request->vessel_etd,
                'vessel_eta' => $request->vessel_eta,
                'loading_date' => $request->loading_date,
                'estimated_payment_date' => $request->estimated_payment_date,
                'freight_amount' => $request->freight_amount ?? '',
                'transporter_id' => $request->transporter_id,
                'c_agent' => $request->c_agent,
                'shipping_line' => $request->shipping_line,
                'empty_container_pickup' => $request->empty_container_pickup,
                'fumigation_by' => $request->has('fumigation_by_hidden') ? $request->fumigation_by_hidden : ($request->has('fumigation_by') ? json_encode($request->fumigation_by) : null),
                'inspection_by' => $request->has('inspection_by_hidden') ? $request->inspection_by_hidden : ($request->has('inspection_by') ? json_encode($request->inspection_by) : null),
                'phyto_certificate' => $request->has('phyto_certificate') ? json_encode($request->phyto_certificate) : null,
                'carton_supplier' => $request->carton_supplier,
                'fumigation_tablets' => $request->fumigation_tablets,
                'fumigation_ref_no' => $request->fumigation_ref_no,
            ]);

            // Update multiple locations
            $deliveryOrder->locations()->delete();
            if ($request->has('locations') && is_array($request->locations)) {
                foreach ($request->locations as $locData) {
                    if (empty($locData['location_id'])) continue;
                    $deliveryOrder->locations()->create([
                        'company_location_id' => $locData['location_id'],
                        'arrival_location_ids' => isset($locData['arrival_ids']) ? implode(',', (array) $locData['arrival_ids']) : null,
                        'sub_arrival_location_ids' => isset($locData['storage_ids']) ? implode(',', (array) $locData['storage_ids']) : null,
                    ]);
                }
            }

            if ($request->filled('packing_items')) {
                foreach ($deliveryOrder->exportPackingItems as $existingItem) {
                    $existingItem->subItems()->delete();
                    $existingItem->delete();
                }

                foreach ($request->packing_items as $index => $itemData) {

                    if (empty($itemData['bag_product_id'])) {
                        continue;
                    }

                    $packingItem = $deliveryOrder->exportPackingItems()->create([
                        'company_location_id' => $itemData['company_location_id'] ?? null,
                        'bag_type_id' => $itemData['bag_product_id'] ?? null,
                        'bag_condition_id' => $itemData['bag_condition_id'] ?? null,
                        'bag_size' => $itemData['bag_size'] ?? 0,
                        'no_of_bags' => $itemData['no_of_bags'] ?? 0,
                        'extra_bags' => $itemData['extra_bags'] ?? 0,
                        'extra_bags_percentage' => $itemData['extra_bags_percentage'] ?? 0,
                        'empty_bags' => $itemData['empty_bags'] ?? 0,
                        'total_bags' => $itemData['total_bags'] ?? 0,
                        'total_kgs' => $itemData['total_kgs'] ?? 0,
                        'metric_tons' => $itemData['metric_tons'] ?? 0,
                        'stuffing_in_container' => $itemData['stuffing_in_container'] ?? 0,
                        'no_of_containers' => $itemData['no_of_containers'] ?? 0,
                        'brand_id' => $itemData['brand_id'] ?? null,
                        'bag_color_id' => $itemData['bag_color_id'] ?? null,
                        'thread_color_id' => $itemData['thread_color_id'] ?? null,
                        'stitching_id' => $itemData['stitching_id'] ?? null,
                        'min_weight_empty_bags' => $itemData['min_weight_empty_bags'] ?? 0,
                    ]);

                    if (isset($itemData['sub_items']) && is_array($itemData['sub_items'])) {
                        foreach ($itemData['sub_items'] as $subItemData) {
                            $packingItem->subItems()->create([
                                'bag_type_id' => $subItemData['bag_product_id'] ?? null,
                                'bag_size_id' => $subItemData['bag_size_id'] ?? null,
                                'no_of_primary_bags' => $subItemData['no_of_primary_bags'] ?? 0,
                                'no_of_bags' => $subItemData['no_of_bags'] ?? 0,
                                'empty_bags' => $subItemData['empty_bags'] ?? 0,
                                'extra_bags' => $subItemData['extra_bags'] ?? 0,
                                'empty_bag_weight' => $subItemData['empty_bag_weight'] ?? 0,
                                'total_bags' => $subItemData['total_bags'] ?? 0,
                                'total_kgs' => $subItemData['total_kgs'] ?? 0,
                                'stitching_id' => $subItemData['stitching_id'] ?? null,
                                'bag_color_id' => $subItemData['bag_color_id'] ?? null,
                                'brand_id' => $subItemData['brand_id'] ?? null,
                                'thread_color_id' => $subItemData['thread_color_id'] ?? null,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Delivery Order updated successfully',
                'data' => $deliveryOrder
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $deliveryOrder = DeliveryOrder::lockForUpdate()->find($id);

            if (!$deliveryOrder) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Order already deleted or not found.',
                ], 404);
            }

            if (
                $deliveryOrder->am_approval_status === "approved" ||
                $deliveryOrder->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Order has been approved/rejected and cannot be deleted.',
                ], 400);
            }

            // delete relations safely
            foreach ($deliveryOrder->exportPackingItems as $item) {
                $item->subItems()->delete();
            }

            $deliveryOrder->exportPackingItems()->delete();
            $deliveryOrder->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Delivery Order deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Failed to delete Delivery Order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrdersByBuyer($buyer_id)
    {
        $export_orders = ExportOrder::with(['packingItems', 'deliveryOrders.exportPackingItems'])
            ->where('buyer_id', $buyer_id)
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()
            ->get()
            ->filter(function ($eo) {
                $totalMt = (float) $eo->packingItems->sum('metric_tons');
                $consumedMt = (float) $eo->deliveryOrders->sum(function ($do) {
                    return $do->exportPackingItems->sum('metric_tons');
                });
                // Only return if more than 0.001 MT is remaining
                return ($totalMt - $consumedMt) > 0.001;
            })
            ->values(); // Reset keys for JSON response

        return response()->json([
            'success' => true,
            'data' => $export_orders
        ]);
    }

    public function getArrivalLocations(Request $request)
    {
        $location_id = $request->location_id;

        $arrival_locations = \App\Models\Master\ArrivalLocation::where('company_location_id', $location_id)->get();

        $data = [];
        foreach ($arrival_locations as $arrival_location) {
            $data[] = [
                'id' => $arrival_location->id,
                'text' => $arrival_location->name,
            ];
        }

        return response()->json($data);
    }

    public function getSubArrivalLocations(Request $request)
    {
        $arrival_ids = (array) $request->arrival_id;

        $sub_arrival_locations = \App\Models\Master\ArrivalSubLocation::whereIn('arrival_location_id', $arrival_ids)->get();

        $data = [];
        foreach ($sub_arrival_locations as $sub_arrival) {
            $data[] = [
                'id' => $sub_arrival->id,
                'text' => $sub_arrival->name . " (" . $sub_arrival->arrivalLocation->name . ")",
            ];
        }

        return response()->json($data);
    }
    public function getFormEUsage($id)
    {
        $formE = ExportFormE::findOrFail($id);
        $totalAllowed = (float) $formE->input_quantity;

        $consumed = DeliveryOrder::with(['exportPackingItems'])
            ->where('export_form_e_id', $id)
            ->get()
            ->sum(function ($do) {
                return $do->exportPackingItems->sum('metric_tons');
            });

        return response()->json([
            'success' => true,
            'total' => round($totalAllowed, 2),
            'consumed' => round($consumed, 2),
            'remaining' => round(max(0, $totalAllowed - $consumed), 2),
            'job_order_no' => $formE->job_order_no ?? ''
        ]);
    }
}
