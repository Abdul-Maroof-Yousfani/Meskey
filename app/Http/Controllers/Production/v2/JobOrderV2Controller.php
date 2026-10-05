<?php

namespace App\Http\Controllers\Production\v2;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagType;
use App\Models\Export\ExportOrder;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\CompanyLocation;
use App\Models\Master\CropYear;
use App\Models\Master\FumigationCompany;
use App\Models\Master\InspectionCompany;
use App\Models\Master\ProductionPhase;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Product;
use App\Models\ProdctionAttribute;
use App\Models\ProductionV2\JobOrderV2;
use App\Models\ProductionV2\ProductionJobOrderPackingItem;
use App\Models\ProductionV2\ProductionJobOrderPackingSubItem;
use App\Models\ProductionV2\ProductionJobOrderPhaseParameter;
use App\Models\ProductionV2\ProductionJobOrderSpecification;
use App\Models\ProductionV2\ProductionPhase1;
use App\Models\ProductionV2\ProductionPhase2;
use App\Models\ProductionV2\ProductionPhase3;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobOrderV2Controller extends Controller
{
    public function index()
    {
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        return view('management.production.v2.job_orders.index', compact('locations'));
    }

    public function getList(Request $request)
    {
        $query = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'creator',
            'phase1',
            'phase2',
            'phase3'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('job_order_no', 'like', $search)
                        ->orWhere('ref_no', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhere('order_description', 'like', $search);
                });
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $q->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('from_date') && $request->filled('to_date'), function ($q) use ($request) {
                $q->whereBetween('job_order_date', [$request->from_date, $request->to_date]);
            });

        $jobOrders = $query->latest()->paginate($request->get('per_page', 25));
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get()->keyBy('id');

        return view('management.production.v2.job_orders.getList', compact('jobOrders', 'allPhases'));
    }

    public function create()
    {
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->get();
        $exportOrders = ExportOrder::where('am_approval_status', 'approved')->latest()->take(50)->get();
        $users = User::get(); // Users for attention_to
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get();

        // Phase 3 Master Datasets
        $cropYears = CropYear::where('status', 'active')->get();
        $brands = Brands::where('status', 1)->get();
        $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(DB::raw('LOWER(name)'), ['bag', 'bags']);
            })
            ->orderBy('name')
            ->get();
        if ($bagProducts->isEmpty()) {
            $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')->orderBy('name')->get();
        }
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $companyLocations = $locations;
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();

        return view('management.production.v2.job_orders.create', compact(
            'locations',
            'products',
            'exportOrders',
            'users',
            'allPhases',
            'cropYears',
            'brands',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'bagColors',
            'sizes',
            'stitchings',
            'attributes'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_location_id' => 'required|exists:company_locations,id',
            'product_id' => 'required|exists:products,id',
            'export_order_id' => 'nullable|exists:export_orders,id',
            'job_order_date' => 'required|date',
            'ref_no' => 'nullable|string|max:100',
            'attention_to' => 'nullable|array',
            'order_description' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $location = CompanyLocation::findOrFail($request->company_location_id);
            $locationPhases = (array)($location->production_phases ?? []);

            $selectedPhases = $request->input('selected_phases');
            if (!empty($selectedPhases) && is_array($selectedPhases)) {
                $phaseIds = array_values(array_intersect(array_map('intval', $selectedPhases), array_map('intval', $locationPhases)));
            } else {
                $phaseIds = array_values(array_map('intval', $locationPhases));
            }

            // Determine running stage: if created from phase 2 -> 2, phase 1 -> 1, phase 3 -> 3
            $initialStage = !empty($phaseIds) ? min($phaseIds) : 1;
            if ($request->has('current_stage') && in_array((int)$request->current_stage, $phaseIds)) {
                $currentStage = (int)$request->current_stage;
            } else {
                $currentStage = $initialStage;
            }

            // Generate unique job order number if not provided or collision exists
            $jobOrderNo = $request->job_order_no;
            if (empty($jobOrderNo) || JobOrderV2::withTrashed()->where('job_order_no', $jobOrderNo)->exists()) {
                $jobOrderNo = self::generateUniqueNumber($request->job_order_date, $location);
            }

            $jobOrderData = [
                'company_id' => Auth::user()?->company_id,
                'company_location_id' => $location->id,
                'product_id' => $request->product_id ?: null,
                'export_order_id' => $request->export_order_id ?: null,
                'job_order_no' => $jobOrderNo,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ? array_values(array_filter($request->attention_to)) : null,
                'order_description' => $request->order_description,
                'remarks' => $request->remarks,
                'active_phases' => $phaseIds,
                'current_stage' => $currentStage,
                'current_phase' => 'phase_' . $currentStage,
                'status' => 'draft',
                'approval_status' => 'pending',
                'maker_id' => Auth::user()?->id,
            ];

            if (in_array(3, $phaseIds)) {
                $jobOrderData['crop_year_id'] = $request->crop_year_id ?: null;
                $jobOrderData['other_specifications'] = $request->other_specifications ?: null;
                $jobOrderData['inspection_company_id'] = $request->inspection_company_id ? array_values(array_filter((array)$request->inspection_company_id)) : null;
                $jobOrderData['arrival_locations'] = $request->arrival_locations ? array_values(array_filter((array)$request->arrival_locations)) : null;
                $jobOrderData['loading_date'] = $request->loading_date ?: null;
                $jobOrderData['packing_description'] = $request->packing_description ?: null;
            }

            $jobOrder = JobOrderV2::create($jobOrderData);

            // Phase 1 (Drying) if enabled
            if (in_array(1, $phaseIds)) {
                $phase1 = ProductionPhase1::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'drying_mode' => $request->p1_drying_mode ?? 'batch',
                    'temperature' => $request->p1_temperature,
                    'moisture_level' => $request->p1_moisture_level ?? 'half_dried',
                    'status' => 'pending',
                    'remarks' => $request->p1_remarks,
                ]);

                if ($request->has('phase1_parameters') && is_array($request->phase1_parameters)) {
                    $p1ParamsArray = [];
                    foreach ($request->phase1_parameters as $param) {
                        if (empty($param['production_attribute_id']) && empty($param['key'])) continue;
                        ProductionJobOrderPhaseParameter::create([
                            'company_id' => Auth::user()?->company_id,
                            'job_order_id' => $jobOrder->id,
                            'phase_id' => 1,
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ]);
                        $p1ParamsArray[] = [
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ];
                    }
                    $phase1->update(['parameters' => $p1ParamsArray]);
                }
            }

            // Phase 2 (Steaming / Parboiling) if enabled
            if (in_array(2, $phaseIds)) {
                $phase2 = ProductionPhase2::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'process_type' => $request->p2_process_type ?? 'steaming',
                    'steam_type' => $request->p2_steam_type ?? 'single_steam',
                    'parboil_soak_hours' => $request->p2_parboil_soak_hours,
                    'parboil_cook_minutes' => $request->p2_parboil_cook_minutes,
                    'parboiled_grade' => $request->p2_parboiled_grade,
                    'status' => 'pending',
                    'remarks' => $request->p2_remarks,
                ]);

                if ($request->has('phase2_parameters') && is_array($request->phase2_parameters)) {
                    $p2ParamsArray = [];
                    foreach ($request->phase2_parameters as $param) {
                        if (empty($param['production_attribute_id']) && empty($param['key'])) continue;
                        ProductionJobOrderPhaseParameter::create([
                            'company_id' => Auth::user()?->company_id,
                            'job_order_id' => $jobOrder->id,
                            'phase_id' => 2,
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ]);
                        $p2ParamsArray[] = [
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ];
                    }
                    $phase2->update(['parameters' => $p2ParamsArray]);
                }
            }

            // Phase 3 (Milling / Old Production Flow) if enabled
            if (in_array(3, $phaseIds)) {
                // Check Export Order capacity if export order is present
                if ($request->export_order_id && $request->has('packing_items') && is_array($request->packing_items)) {
                    $eo = ExportOrder::with(['packingItems'])->find($request->export_order_id);
                    if ($eo) {
                        $totalAllowedMt = $eo->packingItems->sum('metric_tons');
                        $alreadyConsumedMt = JobOrderV2::where('export_order_id', $eo->id)
                            ->where('id', '!=', $jobOrder->id)
                            ->with('packingItems')
                            ->get()
                            ->sum(function ($jo) {
                                return $jo->packingItems->sum('metric_tons');
                            });
                        $currentRequestMt = collect($request->packing_items)->sum('metric_tons');
                        if (($alreadyConsumedMt + $currentRequestMt) > ($totalAllowedMt + 0.001)) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 422,
                                'message' => "Total Metric Tons ($currentRequestMt) exceeds the remaining capacity of Export Order (" . round($totalAllowedMt - $alreadyConsumedMt, 3) . " MT)."
                            ], 422);
                        }
                    }
                }

                // Handle Packing Items
                if ($request->has('packing_items') && is_array($request->packing_items)) {
                    foreach ($request->packing_items as $index => $itemData) {
                        // Skip only if the entire item is blank
                        if (empty($itemData['bag_product_id']) && empty($itemData['brand_id']) && empty($itemData['bag_size']) && empty($itemData['no_of_bags'])) {
                            continue;
                        }
                        $subItems = $itemData['sub_items'] ?? [];
                        unset($itemData['sub_items']);

                        $itemData['job_order_id'] = $jobOrder->id;
                        $itemData['extra_bags'] = $itemData['extra_bags'] ?? 0;
                        $itemData['empty_bags'] = $itemData['empty_bags'] ?? 0;
                        $itemData['extra_bags_percentage'] = $itemData['extra_bags_percentage'] ?? 0;
                        $itemData['min_weight_empty_bags'] = $itemData['min_weight_empty_bags'] ?? 0;
                        $itemData['no_of_containers'] = $itemData['no_of_containers'] ?? 0;
                        $itemData['stuffing_in_container'] = $itemData['stuffing_in_container'] ?? 0;
                        $itemData['bag_size'] = $itemData['bag_size'] ?? 0;
                        $itemData['no_of_bags'] = $itemData['no_of_bags'] ?? 0;

                        // Ensure database non-null columns have safe fallbacks
                        if (empty($itemData['brand_id'])) {
                            $itemData['brand_id'] = Brands::where('status', 1)->first()?->id ?? 1;
                        }
                        if (empty($itemData['bag_product_id'])) {
                            $defaultBagProduct = Product::where('status', 'active')->where('product_type', 'general_items')
                                ->whereHas('category', function ($q) { $q->whereIn(DB::raw('LOWER(name)'), ['bag', 'bags']); })
                                ->first() ?? Product::where('status', 'active')->where('product_type', 'general_items')->first();
                            $itemData['bag_product_id'] = $defaultBagProduct?->id ?? 1;
                        }
                        if (empty($itemData['bag_condition_id'])) {
                            $itemData['bag_condition_id'] = BagCondition::where('status', 1)->first()?->id ?? 1;
                        }
                        if (empty($itemData['bag_color_id'])) {
                            $itemData['bag_color_id'] = Color::where('status', 1)->first()?->id ?? 1;
                        }

                        // Calculate totals from sub-items if present
                        if (!empty($subItems)) {
                            $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                            $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                            $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                                $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                                return ($subItem['no_of_bags'] ?? 0) * (float)$packingSize;
                            });

                            $itemData['total_bags'] = $totalBagsFromSubItems + ($itemData['extra_bags'] ?? 0) + ($itemData['empty_bags'] ?? 0);
                            $itemData['total_kgs'] = $totalKgsFromSubItems;
                            $itemData['metric_tons'] = $itemData['total_kgs'] / 1000;
                        } else {
                            if (empty($itemData['total_bags']) || $itemData['total_bags'] == 0) {
                                $itemData['total_bags'] = (int)($itemData['no_of_bags'] ?? 0) + (int)($itemData['extra_bags'] ?? 0) + (int)($itemData['empty_bags'] ?? 0);
                            }
                            if (empty($itemData['total_kgs']) || $itemData['total_kgs'] == 0) {
                                $itemData['total_kgs'] = (float)($itemData['no_of_bags'] ?? 0) * (float)($itemData['bag_size'] ?? 0);
                            }
                            if (empty($itemData['metric_tons']) || $itemData['metric_tons'] == 0) {
                                $itemData['metric_tons'] = (float)($itemData['total_kgs'] ?? 0) / 1000;
                            }
                        }

                        $packingItem = ProductionJobOrderPackingItem::create($itemData);

                        // Create sub-items
                        if (!empty($subItems)) {
                            foreach ($subItems as $subIdx => $subItemData) {
                                if (empty($subItemData['bag_product_id']) && empty($subItemData['brand_id']) && empty($subItemData['bag_size_id'])) {
                                    continue;
                                }
                                $subItemData['job_order_packing_item_id'] = $packingItem->id;
                                $subItemData['empty_bags'] = $subItemData['empty_bags'] ?? 0;
                                $subItemData['extra_bags'] = $subItemData['extra_bags'] ?? 0;
                                $subItemData['extra_bags_percentage'] = $subItemData['extra_bags_percentage'] ?? 0;
                                $subItemData['empty_bag_weight'] = $subItemData['empty_bag_weight'] ?? 0;
                                $subItemData['no_of_primary_bags'] = $subItemData['no_of_primary_bags'] ?? 0;

                                // Handle file attachment
                                if ($request->hasFile("packing_items.{$index}.sub_items.{$subIdx}.attachment")) {
                                    $file = $request->file("packing_items.{$index}.sub_items.{$subIdx}.attachment");
                                    $subItemData['attachment'] = $file->store('job_orders/attachments', 'public');
                                }

                                ProductionJobOrderPackingSubItem::create($subItemData);
                            }
                        }
                    }
                }

                // Handle Specifications
                if ($request->has('specifications') && is_array($request->specifications)) {
                    foreach ($request->specifications as $spec) {
                        if (!empty($spec['spec_name']) || !empty($spec['product_slab_type_id'])) {
                            ProductionJobOrderSpecification::create([
                                'job_order_id' => $jobOrder->id,
                                'product_slab_type_id' => $spec['product_slab_type_id'] ?? 0,
                                'spec_name' => $spec['spec_name'] ?? '',
                                'spec_value' => $spec['spec_value'] ?? '',
                                'uom' => $spec['uom'] ?? null,
                                'value_type' => $spec['value_type'] ?? 'min',
                            ]);
                        }
                    }
                }

                // Handle Container Protection & Packing Materials
                if ($request->has('container_protection_items') && is_array($request->container_protection_items)) {
                    $containerProtectionData = [];
                    foreach ($request->container_protection_items as $cpItem) {
                        if (!empty($cpItem['product_id']) && isset($cpItem['quantity_per_container'])) {
                            $containerProtectionData[$cpItem['product_id']] = [
                                'quantity_per_container' => $cpItem['quantity_per_container'] ?? 0
                            ];
                        }
                    }
                    if (!empty($containerProtectionData)) {
                        $jobOrder->containerProtectionItems()->sync($containerProtectionData);
                    }
                }

                // Create ProductionPhase3 entry
                ProductionPhase3::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'milling_type' => $request->p3_milling_type ?? 'direct_milling',
                    'storage_type' => 'flat_storage',
                    'status' => 'pending',
                    'remarks' => $request->p3_remarks ?? $request->remarks,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Job Order created successfully',
                'job_order' => $jobOrder,
                'redirect_url' => route('production.job-orders.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error creating Job Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $jobOrder = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'maker',
            'poster',
            'agreeor',
            'productionPhase1',
            'productionPhase2',
            'productionPhase3',
            'cropYear',
            'packingItems.companyLocation',
            'packingItems.bagProduct',
            'packingItems.bagCondition',
            'packingItems.brand',
            'packingItems.bagColor',
            'packingItems.threadColor',
            'packingItems.stitching',
            'packingItems.subItems.bagProduct',
            'packingItems.subItems.bagSize',
            'packingItems.subItems.stitching',
            'packingItems.subItems.bagColor',
            'packingItems.subItems.brand',
            'packingItems.subItems.threadColor',
            'specifications.productSlabType',
            'containerProtectionItems',
            'phase1Parameters.attribute',
            'phase2Parameters.attribute',
        ])->findOrFail($id);

        $activePhases = $jobOrder->getActivePhaseModels();

        return view('management.production.v2.job_orders.show', compact('jobOrder', 'activePhases'));
    }

    public function edit($id)
    {
        $jobOrder = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'productionPhase1',
            'productionPhase2',
            'productionPhase3',
            'cropYear',
            'packingItems.companyLocation',
            'packingItems.bagProduct',
            'packingItems.bagCondition',
            'packingItems.brand',
            'packingItems.bagColor',
            'packingItems.threadColor',
            'packingItems.stitching',
            'packingItems.subItems.bagProduct',
            'packingItems.subItems.bagSize',
            'packingItems.subItems.stitching',
            'packingItems.subItems.bagColor',
            'packingItems.subItems.brand',
            'packingItems.subItems.threadColor',
            'specifications.productSlabType',
            'containerProtectionItems',
            'phase1Parameters.attribute',
            'phase2Parameters.attribute',
        ])->findOrFail($id);

        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->get();
        $exportOrders = ExportOrder::where('am_approval_status', 'approved')->latest()->take(50)->get();
        $users = User::get(); // Users for attention_to

        $activePhases = $jobOrder->getActivePhaseModels();
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get();

        // Phase 3 Master Datasets
        $cropYears = CropYear::where('status', 'active')->get();
        $brands = Brands::where('status', 1)->get();
        $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(DB::raw('LOWER(name)'), ['bag', 'bags']);
            })
            ->orderBy('name')
            ->get();
        if ($bagProducts->isEmpty()) {
            $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')->orderBy('name')->get();
        }
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $companyLocations = $locations;
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();

        return view('management.production.v2.job_orders.edit', compact(
            'jobOrder',
            'locations',
            'products',
            'exportOrders',
            'users',
            'activePhases',
            'allPhases',
            'cropYears',
            'brands',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'bagColors',
            'sizes',
            'stitchings',
            'attributes'
        ));
    }

    public function update(Request $request, $id)
    {
        $jobOrder = JobOrderV2::findOrFail($id);

        $request->validate([
            'company_location_id' => 'required|exists:company_locations,id',
            'product_id' => 'required|exists:products,id',
            'export_order_id' => 'nullable|exists:export_orders,id',
            'job_order_date' => 'required|date',
            'ref_no' => 'nullable|string|max:100',
            'attention_to' => 'nullable|array',
            'order_description' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $location = CompanyLocation::findOrFail($request->company_location_id);
            $locationPhases = (array)($location->production_phases ?? []);

            $selectedPhases = $request->input('selected_phases');
            if (!empty($selectedPhases) && is_array($selectedPhases)) {
                $phaseIds = array_values(array_intersect(array_map('intval', $selectedPhases), array_map('intval', $locationPhases)));
            } else {
                $phaseIds = array_values(array_map('intval', $locationPhases));
            }

            // Determine running stage
            $initialStage = !empty($phaseIds) ? min($phaseIds) : 1;
            if ($request->has('current_stage') && in_array((int)$request->current_stage, $phaseIds)) {
                $currentStage = (int)$request->current_stage;
            } elseif ($jobOrder->current_stage && in_array((int)$jobOrder->current_stage, $phaseIds)) {
                $currentStage = (int)$jobOrder->current_stage;
            } else {
                $currentStage = $initialStage;
            }

            $jobOrderData = [
                'company_location_id' => $location->id,
                'product_id' => $request->product_id ?: null,
                'export_order_id' => $request->export_order_id ?: null,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ? array_values(array_filter($request->attention_to)) : null,
                'order_description' => $request->order_description,
                'remarks' => $request->remarks,
                'active_phases' => $phaseIds,
                'current_stage' => $currentStage,
                'current_phase' => 'phase_' . $currentStage,
            ];

            if (in_array(3, $phaseIds)) {
                $jobOrderData['crop_year_id'] = $request->crop_year_id ?: null;
                $jobOrderData['other_specifications'] = $request->other_specifications ?: null;
                $jobOrderData['inspection_company_id'] = $request->inspection_company_id ? array_values(array_filter((array)$request->inspection_company_id)) : null;
                $jobOrderData['arrival_locations'] = $request->arrival_locations ? array_values(array_filter((array)$request->arrival_locations)) : null;
                $jobOrderData['loading_date'] = $request->loading_date ?: null;
                $jobOrderData['packing_description'] = $request->packing_description ?: null;
            }

            $jobOrder->update($jobOrderData);

            // Phase 1 (Drying) update/create
            if (in_array(1, $phaseIds)) {
                $phase1 = ProductionPhase1::updateOrCreate(
                    ['job_order_id' => $jobOrder->id],
                    [
                        'company_id' => Auth::user()?->company_id,
                        'company_location_id' => $location->id,
                        'drying_mode' => $request->p1_drying_mode ?? 'batch',
                        'temperature' => $request->p1_temperature,
                        'moisture_level' => $request->p1_moisture_level ?? 'half_dried',
                        'remarks' => $request->p1_remarks,
                    ]
                );

                ProductionJobOrderPhaseParameter::where('job_order_id', $jobOrder->id)->where('phase_id', 1)->forceDelete();
                $p1ParamsArray = [];
                if ($request->has('phase1_parameters') && is_array($request->phase1_parameters)) {
                    foreach ($request->phase1_parameters as $param) {
                        if (empty($param['production_attribute_id']) && empty($param['key'])) continue;
                        ProductionJobOrderPhaseParameter::create([
                            'company_id' => Auth::user()?->company_id,
                            'job_order_id' => $jobOrder->id,
                            'phase_id' => 1,
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ]);
                        $p1ParamsArray[] = [
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ];
                    }
                }
                $phase1->update(['parameters' => $p1ParamsArray]);
            } else {
                ProductionPhase1::where('job_order_id', $jobOrder->id)->delete();
                ProductionJobOrderPhaseParameter::where('job_order_id', $jobOrder->id)->where('phase_id', 1)->delete();
            }

            // Phase 2 (Steaming / Parboiling) update/create
            if (in_array(2, $phaseIds)) {
                $phase2 = ProductionPhase2::updateOrCreate(
                    ['job_order_id' => $jobOrder->id],
                    [
                        'company_id' => Auth::user()?->company_id,
                        'company_location_id' => $location->id,
                        'process_type' => $request->p2_process_type ?? 'steaming',
                        'steam_type' => $request->p2_steam_type ?? 'single_steam',
                        'parboil_soak_hours' => $request->p2_parboil_soak_hours,
                        'parboil_cook_minutes' => $request->p2_parboil_cook_minutes,
                        'parboiled_grade' => $request->p2_parboiled_grade,
                        'remarks' => $request->p2_remarks,
                    ]
                );

                ProductionJobOrderPhaseParameter::where('job_order_id', $jobOrder->id)->where('phase_id', 2)->forceDelete();
                $p2ParamsArray = [];
                if ($request->has('phase2_parameters') && is_array($request->phase2_parameters)) {
                    foreach ($request->phase2_parameters as $param) {
                        if (empty($param['production_attribute_id']) && empty($param['key'])) continue;
                        ProductionJobOrderPhaseParameter::create([
                            'company_id' => Auth::user()?->company_id,
                            'job_order_id' => $jobOrder->id,
                            'phase_id' => 2,
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ]);
                        $p2ParamsArray[] = [
                            'production_attribute_id' => $param['production_attribute_id'] ?? null,
                            'key' => $param['key'] ?? '',
                            'type' => $param['type'] ?? 'text',
                            'value' => $param['value'] ?? '',
                        ];
                    }
                }
                $phase2->update(['parameters' => $p2ParamsArray]);
            } else {
                ProductionPhase2::where('job_order_id', $jobOrder->id)->delete();
                ProductionJobOrderPhaseParameter::where('job_order_id', $jobOrder->id)->where('phase_id', 2)->delete();
            }

            // Phase 3 (Milling / Old Production Flow) if enabled
            if (in_array(3, $phaseIds)) {
                // Handle Packing Items
                if ($request->has('packing_items') && is_array($request->packing_items)) {
                    // Delete previous packing items to cleanly replace (cascades to subItems)
                    $jobOrder->packingItems()->delete();

                    foreach ($request->packing_items as $index => $itemData) {
                        // Skip only if the entire item is blank
                        if (empty($itemData['bag_product_id']) && empty($itemData['brand_id']) && empty($itemData['bag_size']) && empty($itemData['no_of_bags'])) {
                            continue;
                        }
                        $subItems = $itemData['sub_items'] ?? [];
                        unset($itemData['sub_items']);

                        $itemData['job_order_id'] = $jobOrder->id;
                        $itemData['extra_bags'] = $itemData['extra_bags'] ?? 0;
                        $itemData['empty_bags'] = $itemData['empty_bags'] ?? 0;
                        $itemData['extra_bags_percentage'] = $itemData['extra_bags_percentage'] ?? 0;
                        $itemData['min_weight_empty_bags'] = $itemData['min_weight_empty_bags'] ?? 0;
                        $itemData['no_of_containers'] = $itemData['no_of_containers'] ?? 0;
                        $itemData['stuffing_in_container'] = $itemData['stuffing_in_container'] ?? 0;
                        $itemData['bag_size'] = $itemData['bag_size'] ?? 0;
                        $itemData['no_of_bags'] = $itemData['no_of_bags'] ?? 0;

                        // Ensure database non-null columns have safe fallbacks
                        if (empty($itemData['brand_id'])) {
                            $itemData['brand_id'] = Brands::where('status', 1)->first()?->id ?? 1;
                        }
                        if (empty($itemData['bag_product_id'])) {
                            $defaultBagProduct = Product::where('status', 'active')->where('product_type', 'general_items')
                                ->whereHas('category', function ($q) { $q->whereIn(DB::raw('LOWER(name)'), ['bag', 'bags']); })
                                ->first() ?? Product::where('status', 'active')->where('product_type', 'general_items')->first();
                            $itemData['bag_product_id'] = $defaultBagProduct?->id ?? 1;
                        }
                        if (empty($itemData['bag_condition_id'])) {
                            $itemData['bag_condition_id'] = BagCondition::where('status', 1)->first()?->id ?? 1;
                        }
                        if (empty($itemData['bag_color_id'])) {
                            $itemData['bag_color_id'] = Color::where('status', 1)->first()?->id ?? 1;
                        }

                        if (!empty($subItems)) {
                            $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                            $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                            $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                                $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                                return ($subItem['no_of_bags'] ?? 0) * (float)$packingSize;
                            });

                            $itemData['total_bags'] = $totalBagsFromSubItems + ($itemData['extra_bags'] ?? 0) + ($itemData['empty_bags'] ?? 0);
                            $itemData['total_kgs'] = $totalKgsFromSubItems;
                            $itemData['metric_tons'] = $itemData['total_kgs'] / 1000;
                        } else {
                            if (empty($itemData['total_bags']) || $itemData['total_bags'] == 0) {
                                $itemData['total_bags'] = (int)($itemData['no_of_bags'] ?? 0) + (int)($itemData['extra_bags'] ?? 0) + (int)($itemData['empty_bags'] ?? 0);
                            }
                            if (empty($itemData['total_kgs']) || $itemData['total_kgs'] == 0) {
                                $itemData['total_kgs'] = (float)($itemData['no_of_bags'] ?? 0) * (float)($itemData['bag_size'] ?? 0);
                            }
                            if (empty($itemData['metric_tons']) || $itemData['metric_tons'] == 0) {
                                $itemData['metric_tons'] = (float)($itemData['total_kgs'] ?? 0) / 1000;
                            }
                        }

                        $packingItem = ProductionJobOrderPackingItem::create($itemData);

                        if (!empty($subItems)) {
                            foreach ($subItems as $subIdx => $subItemData) {
                                if (empty($subItemData['bag_product_id']) && empty($subItemData['brand_id']) && empty($subItemData['bag_size_id'])) {
                                    continue;
                                }
                                $subItemData['job_order_packing_item_id'] = $packingItem->id;
                                $subItemData['empty_bags'] = $subItemData['empty_bags'] ?? 0;
                                $subItemData['extra_bags'] = $subItemData['extra_bags'] ?? 0;
                                $subItemData['extra_bags_percentage'] = $subItemData['extra_bags_percentage'] ?? 0;
                                $subItemData['empty_bag_weight'] = $subItemData['empty_bag_weight'] ?? 0;
                                $subItemData['no_of_primary_bags'] = $subItemData['no_of_primary_bags'] ?? 0;

                                if ($request->hasFile("packing_items.{$index}.sub_items.{$subIdx}.attachment")) {
                                    $file = $request->file("packing_items.{$index}.sub_items.{$subIdx}.attachment");
                                    $subItemData['attachment'] = $file->store('job_orders/attachments', 'public');
                                } elseif (!empty($subItemData['existing_attachment'])) {
                                    $subItemData['attachment'] = $subItemData['existing_attachment'];
                                }
                                unset($subItemData['existing_attachment']);

                                ProductionJobOrderPackingSubItem::create($subItemData);
                            }
                        }
                    }
                }

                // Handle Specifications
                if ($request->has('specifications') && is_array($request->specifications)) {
                    $jobOrder->specifications()->delete();
                    foreach ($request->specifications as $spec) {
                        if (!empty($spec['spec_name']) || !empty($spec['product_slab_type_id'])) {
                            ProductionJobOrderSpecification::create([
                                'job_order_id' => $jobOrder->id,
                                'product_slab_type_id' => $spec['product_slab_type_id'] ?? 0,
                                'spec_name' => $spec['spec_name'] ?? '',
                                'spec_value' => $spec['spec_value'] ?? '',
                                'uom' => $spec['uom'] ?? null,
                                'value_type' => $spec['value_type'] ?? 'min',
                            ]);
                        }
                    }
                }

                // Handle Container Protection Items
                if ($request->has('container_protection_items') && is_array($request->container_protection_items)) {
                    $containerProtectionData = [];
                    foreach ($request->container_protection_items as $cpItem) {
                        if (!empty($cpItem['product_id']) && isset($cpItem['quantity_per_container'])) {
                            $containerProtectionData[$cpItem['product_id']] = [
                                'quantity_per_container' => $cpItem['quantity_per_container'] ?? 0
                            ];
                        }
                    }
                    $jobOrder->containerProtectionItems()->sync($containerProtectionData);
                }

                ProductionPhase3::updateOrCreate(
                    ['job_order_id' => $jobOrder->id],
                    [
                        'company_id' => Auth::user()?->company_id,
                        'company_location_id' => $location->id,
                        'milling_type' => $request->p3_milling_type ?? 'direct_milling',
                        'storage_type' => 'flat_storage',
                        'remarks' => $request->p3_remarks ?? $request->remarks,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Job Order updated successfully',
                'redirect_url' => route('production.job-orders.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error updating Job Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $jobOrder = JobOrderV2::findOrFail($id);
        $jobOrder->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Job Order deleted successfully',
        ]);
    }

    /**
     * AJAX endpoint to fetch enabled phases for a given location
     */
    public function getPhasesByLocation($locationId)
    {
        $location = CompanyLocation::find($locationId);
        if (!$location) {
            return response()->json([
                'status' => 404,
                'message' => 'Location not found',
                'phases' => []
            ]);
        }

        $phaseIds = $location->production_phases ?? [];
        $phases = ProductionPhase::whereIn('id', (array)$phaseIds)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 200,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'location_code' => $location->code,
            'phase_ids' => $phaseIds,
            'phases' => $phases,
        ]);
    }

    /**
     * Generate unique Job Order V2 number: [LOC]-JO-V2-[NUM]-[DATE]
     */
    public static function generateUniqueNumber($dateParam = null, $locationParam = null)
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        $locationCode = 'LOC';
        if ($locationParam) {
            if ($locationParam instanceof CompanyLocation) {
                $locationCode = $locationParam->code ?: 'LOC';
            } elseif (is_numeric($locationParam)) {
                $loc = CompanyLocation::find($locationParam);
                $locationCode = $loc?->code ?: 'LOC';
            } elseif (is_string($locationParam)) {
                $loc = CompanyLocation::where('code', $locationParam)->first();
                $locationCode = $loc ? $loc->code : $locationParam;
            }
        }

        $locationCode = strtoupper(trim($locationCode ?: 'LOC'));
        $prefix = $locationCode . '-JO-V2';
        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = JobOrderV2::withTrashed()
            ->where('job_order_no', 'like', $searchPattern)
            ->pluck('job_order_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || JobOrderV2::withTrashed()->where('job_order_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    /**
     * AJAX endpoint to get unique Job Order Number
     */
    public function getNumber(Request $request)
    {
        $dateParam = $request->job_order_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->location_id ?? $request->company_location_id ?? null;
        $number = self::generateUniqueNumber($dateParam, $locationParam);

        return response()->json([
            'status' => 200,
            'job_order_no' => $number,
        ]);
    }
}
