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
                $searchTerm = '%'.$request->search.'%';
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

        return view('management.export.delivery-order.create', compact(
            'buyers', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations', 'companyLocations', 'bagConditions', 'stitchings', 'inspectionCompanies'
        ));
    }

    public function getExportOrderDetails($id)
    {
        $exportOrder = ExportOrder::where('am_approval_status', 'approved')->with([
            'product', 
            'specifications.productSlabType', 
            'packingItems.subItems.bagType',
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
            'exportSoda.product', // Added for Sauda details
            'quotation.product'   // Added for Quotation details
        ])->findOrFail($id);

        $deliveryOrders = DeliveryOrder::with('exportPackingItems')->where('export_order_id', $id)->get();
        $totalEoMt = $exportOrder->packingItems->sum('metric_tons');
        $consumedMt = $deliveryOrders->sum(function($do) {
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
                    'bag_type_name'  => $sub->bagType->name ?? '',
                    'bag_size_id'    => $sub->bag_size_id,
                    'stitching_id'   => $sub->stitching_id,
                    'bag_color_id'   => $sub->bag_color_id,
                    'brand_id'       => $sub->brand_id,
                    'thread_color_id' => $sub->thread_color_id,
                    'no_of_primary_bags' => $sub->no_of_primary_bags,
                    'no_of_bags'     => $subRemainingBags,
                    'empty_bags'     => $sub->empty_bags,
                    'extra_bags'     => $sub->extra_bags,
                    'extra_bags_percentage' => ($sub->no_of_bags > 0 && $sub->extra_bags > 0) ? round(($sub->extra_bags / $sub->no_of_bags) * 100, 2) : 0,
                    'empty_bag_weight' => $sub->empty_bag_weight,
                    'total_bags'     => $subRemainingBags + ($sub->extra_bags ?? 0) + ($sub->empty_bags ?? 0),
                ];
            })->values();

            return [
                'brand_id'          => $item->brand_id,
                'bag_product_id'    => $item->bag_type_id,
                'bag_type_name'     => $item->bagType->name ?? '',
                'bag_condition_id'  => $item->bag_condition_id,
                'bag_color_id'      => $item->bag_color_id,
                'thread_color_id'   => $item->thread_color_id,
                'stitching_id'      => $item->stitching_id,
                'bag_size'          => $item->bag_size,
                'no_of_bags'        => $remainingBagsInItem,
                'extra_bags'        => $item->extra_bags,
                'extra_bags_percentage' => $item->extra_bags_percentage ?? (($item->no_of_bags > 0 && $item->extra_bags > 0) ? round(($item->extra_bags / $item->no_of_bags) * 100, 2) : 0),
                'empty_bags'        => $item->empty_bags,
                'total_bags'        => $remainingBagsInItem + ($item->extra_bags ?? 0) + ($item->empty_bags ?? 0),
                'total_kgs'         => $remainingMtInItem * 1000,
                'metric_tons'       => $remainingMtInItem,
                'stuffing_in_container' => $item->stuffing_in_container,
                'no_of_containers'  => $item->no_of_containers,
                'min_weight_empty_bags' => $item->min_weight_empty_bags,
                'fumigation_company_id' => $item->fumigation_company_id,
                'sub_items'         => $subItems,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'export_order' => $exportOrder,
                'packing_items_autofill' => $packingItems,
                'total_eo_mt' => round($totalEoMt, 3),
                'consumed_mt' => round($consumedMt, 3),
                'remaining_mt' => round($remainingMt, 3)
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required|exists:export_orders,id',
            'buyer_id' => 'required|exists:customers,id',
            'export_form_e_id' => 'required|exists:export_form_es,id',
            'packing_items' => 'required|array',
        ]);

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

        $deliveryOrder = DeliveryOrder::create([
            'type' => 'export_order',
            'export_order_id' => $request->export_order_id,
            'customer_id' => $request->buyer_id, // Use customer_id consistently
            'export_form_e_id' => $request->export_form_e_id,
            'remarks' => $request->remarks,
            'created_by' => auth()->user() ? auth()->user()->id : null,
            'reference_no' => $request->reference_no, 
            'ref_no' => $request->ref_no,
            'location_id' => $request->location_id ?? null,
            'arrival_location_id' => $request->arrival_id ? implode(',', (array)$request->arrival_id) : null,
            'sub_arrival_location_id' => $request->storage_id ? implode(',', (array)$request->storage_id) : null,
            'am_approval_status' => 'pending',
        ]);

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
                'fumigation_company_id' => isset($itemData['fumigation_company_id']) ? json_encode((array)$itemData['fumigation_company_id']) : null,
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
        $fumigationCompanies = InspectionCompany::all();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();

        return view('management.export.delivery-order.show', compact(
            'deliveryOrder', 'exportOrderData', 'brands', 'bagColors', 
            'threadColors', 'stitchings', 'fumigationCompanies', 'bagTypes', 'bagConditions',
            'totalAllowedMt', 'alreadyConsumedMt', 'remainingMt', 'currentRequestMt'
        ));
    }

    public function edit($id): View
    {
        $deliveryOrder = DeliveryOrder::with([
            'exportPackingItems.subItems',
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
            'exportOrder.packingItems'
        ])->findOrFail($id);
        $buyers = Customer::get();
        $export_orders = ExportOrder::where('am_approval_status', 'approved')->latest()->get();

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

        return view('management.export.delivery-order.edit', compact(
            'deliveryOrder', 'buyers', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations', 'companyLocations', 'bagConditions', 'stitchings', 'inspectionCompanies',
            'totalAllowedMt', 'alreadyConsumedMt', 'remainingMt', 'currentRequestMt'
        ));
    }

    public function update(Request $request, $id)
    {
        $deliveryOrder = DeliveryOrder::findOrFail($id);
        $request->validate([
            'export_form_e_id' => 'required|exists:export_form_es,id',
            'packing_items' => 'required|array',
        ]);

        DB::beginTransaction();

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
            'location_id' => $request->location_id ?? null,
            'arrival_location_id' => $request->arrival_id ? implode(',', (array)$request->arrival_id) : null,
            'sub_arrival_location_id' => $request->storage_id ? implode(',', (array)$request->storage_id) : null,
            'am_approval_status' => 'pending',
            'am_change_made' => 1
        ]);

        if ($request->filled('packing_items')) {
            foreach ($deliveryOrder->exportPackingItems as $existingItem) {
                $existingItem->subItems()->delete();
                $existingItem->delete();
            }

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
                    'fumigation_company_id' => isset($itemData['fumigation_company_id']) ? json_encode((array)$itemData['fumigation_company_id']) : null,
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
    }

    public function destroy($id)
    {
        $deliveryOrder = DeliveryOrder::findOrFail($id);
        $deliveryOrder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Export Delivery Order deleted successfully.'
        ], 200);
    }

    public function getOrdersByBuyer($buyer_id)
    {
        $export_orders = ExportOrder::with(['packingItems', 'deliveryOrders.exportPackingItems'])
                ->where('buyer_id', $buyer_id)
                ->where('am_approval_status', 'approved')
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
            'remaining' => round(max(0, $totalAllowed - $consumed), 2)
        ]);
    }
}
