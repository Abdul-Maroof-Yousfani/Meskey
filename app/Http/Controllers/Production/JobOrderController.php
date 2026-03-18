<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\JobOrderRequest;
use App\Models\Master\CropYear;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Production\JobOrder\{
    JobOrderPackingItem,
    JobOrderPackingSubItem,
    JobOrder,
    JobOrderSpecification
};
use App\Models\Master\{InspectionCompany, FumigationCompany, CompanyLocation, ProductSlab, ArrivalLocation, Brands, Color};
use App\Models\{Product, BagCondition, BagType};
use Illuminate\Http\Request;
use App\Models\User;
use DB;
class JobOrderController extends Controller
{
    public function index()
    {
        return view('management.production.job_orders.index');
    }

    public function getList(Request $request)
    {
        $job_orders = JobOrder::with(['product', 'productionOutputs.productionVoucher.location', 'packingItems'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('job_order_no', 'like', $searchTerm)
                        // ->orWhere('job_order_no', 'like', $searchTerm)
                        ->orWhere('ref_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        // Calculate location-wise allocated and produced quantities for each job order
        foreach ($job_orders as $job_order) {
            $producedByLocation = [];

            // First, get allocated quantities from packing items (grouped by location)
            $allocatedByLocation = [];
            foreach ($job_order->packingItems as $packingItem) {
                if ($packingItem->company_location_id) {
                    $locationId = $packingItem->company_location_id;
                    if (!isset($allocatedByLocation[$locationId])) {
                        $allocatedByLocation[$locationId] = [
                            'location_name' => $packingItem->companyLocation->name ?? 'N/A',
                            'allocated_qty' => 0
                        ];
                    }
                    $allocatedByLocation[$locationId]['allocated_qty'] += $packingItem->total_kgs ?? 0;
                }
            }

            // Now, get produced quantities (only matching product outputs)
            foreach ($job_order->productionOutputs->where('product_id', $job_order->product_id) as $output) {
                if ($output->productionVoucher && $output->productionVoucher->location) {
                    $locationId = $output->productionVoucher->location->id;

                    if (!isset($producedByLocation[$locationId])) {
                        $producedByLocation[$locationId] = [
                            'location_name' => $output->productionVoucher->location->name,
                            'produced_qty' => 0
                        ];
                    }
                    $producedByLocation[$locationId]['produced_qty'] += $output->qty ?? 0;
                }
            }

            // Merge allocated and produced data
            $locationData = [];
            foreach ($allocatedByLocation as $locationId => $allocatedData) {
                $producedQty = $producedByLocation[$locationId]['produced_qty'] ?? 0;
                $locationData[$locationId] = [
                    'location_name' => $allocatedData['location_name'],
                    'allocated_qty' => $allocatedData['allocated_qty'],
                    'produced_qty' => $producedQty,
                    'remaining_qty' => $allocatedData['allocated_qty'] - $producedQty
                ];
            }

            // Also include locations that have production but no allocation (if any)
            foreach ($producedByLocation as $locationId => $producedData) {
                if (!isset($locationData[$locationId])) {
                    $locationData[$locationId] = [
                        'location_name' => $producedData['location_name'],
                        'allocated_qty' => 0,
                        'produced_qty' => $producedData['produced_qty'],
                        'remaining_qty' => -$producedData['produced_qty']
                    ];
                }
            }

            $job_order->producedByLocation = $locationData;
        }

        return view('management.production.job_orders.getList', compact('job_orders'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->get();
        $bagProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['bag', 'bags']);
            })
            ->get();
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // Users for attention_to
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        return view('management.production.job_orders.create', compact(
            'products',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users',
            'cropYears',
            'sizes',
            'stitchings'
        ));
    }

    public function store(JobOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $locationCode = CompanyLocation::where('id', $request->company_location_id)
                ->value('code');

            $uniqueJobNo = generateUniversalUniqueNo('job_orders', [
                'prefix' => 'JOB',
                // 'location' => $locationCode,
                'column' => 'job_order_no',
                'with_date' => 1,
                'custom_date' => $request->job_order_date,
                'date_format' => 'Y',
                'serial_at_end' => 1,
            ]);

            $jobOrderData = $request->only([
                'job_order_no',
                'job_order_date',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                // 'delivery_date',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
            ]);

            $jobOrderData['company_id'] = $request->company_id;
            $jobOrderData['job_order_no'] = $uniqueJobNo;
            // $jobOrderData['company_location_id'] = $request->company_location_id;
            $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
            $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
            // $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

            $jobOrder = JobOrder::create($jobOrderData);

            // Handle location details
            $locationDetails = $request->location_details ?? [];

            foreach ($request->packing_items as $item) {
                // Extract sub-items if they exist
                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                // Merge location details if exists
                $locationId = $item['company_location_id'] ?? null;
                if ($locationId && isset($locationDetails[$locationId])) {
                    $item['no_of_containers'] = $locationDetails[$locationId]['no_of_containers'] ?? 0;
                    $item['description'] = $locationDetails[$locationId]['description'] ?? null;
                    $item['location_instruction'] = $locationDetails[$locationId]['location_instruction'] ?? null;
                }

                // Calculate totals from sub-items
                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) {
                        return ($subItem['no_of_bags'] ?? 0) * ($subItem['bag_size'] ?? 0);
                    });

                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = $totalKgsFromSubItems;
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                }

                // // Store bag_type_id as JSON array
                // if (isset($item['bag_type_id']) && is_array($item['bag_type_id'])) {
                //     $item['bag_type_id'] = json_encode($item['bag_type_id']);
                // }

                // Create packing item
                $packingItem = $jobOrder->packingItems()->create($item);

                // Create sub-items
                if (!empty($subItems)) {
                    foreach ($subItems as $subItem) {
                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            foreach ($request->specifications as $spec) {
                $jobOrder->specifications()->create([
                    'product_slab_type_id' => $spec['product_slab_type_id'],
                    'spec_name' => $spec['spec_name'],
                    'spec_value' => $spec['spec_value'],
                    'uom' => $spec['uom'],
                    'value_type' => $spec['value_type']
                ]);
            }

            // Handle Container Protection & Packing Materials
            if ($request->has('container_protection_items') && !empty($request->container_protection_items)) {
                $containerProtectionData = [];
                foreach ($request->container_protection_items as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_per_container'])) {
                        $containerProtectionData[$item['product_id']] = [
                            'quantity_per_container' => $item['quantity_per_container'] ?? 0
                        ];
                    }
                }
                if (!empty($containerProtectionData)) {
                    $jobOrder->containerProtectionItems()->sync($containerProtectionData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order created successfully.',
                'data' => $jobOrder
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id, Request $request)
    {
        // dd($request->all());
        // $jobOrder = JobOrder::with(['packingItems:where(company_location_id, $request->company_location_id)', 'specifications', 'product'])->findOrFail($id);
        $jobOrder = JobOrder::with([
            'packingItems' => function ($query) use ($request) {
                $query->when($request->filled('company_location_id'), function ($q) use ($request) {
                    $q->where('company_location_id', $request->company_location_id);
                });
                $query->with('subItems');
            },
            'specifications',
            'product',
            'containerProtectionItems'
        ])->findOrFail($id);
        //    dd($jobOrder);
        $products = Product::where('status', 1)->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::get();
        $users = User::get();
        $bagProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['bag', 'bags']);
            })
            ->get();
        // $sizes = Size::where('status', 'active')->get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        // dd($bagColors);
        return view('management.production.job_orders.edit', compact(
            'jobOrder',
            'products',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users',
            'cropYears',
            'sizes',
            'stitchings'
        ));
    }


    public function update(JobOrderRequest $request, JobOrder $jobOrder)
    {
        DB::beginTransaction();

        try {
            $jobOrderData = $request->only([
                'job_order_no',
                'job_order_date',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                // 'delivery_date',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
            ]);

            // $jobOrderData['location'] = $request->location;
            $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
            $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
            // $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

            $jobOrder->update($jobOrderData);

            // Delete existing packing items and their sub-items (cascade)
            $jobOrder->packingItems()->delete();

            // Handle location details
            $locationDetails = $request->location_details ?? [];

            foreach ($request->packing_items as $item) {
                // Extract sub-items if they exist
                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                // Merge location details if exists
                $locationId = $item['company_location_id'] ?? null;
                if ($locationId && isset($locationDetails[$locationId])) {
                    $item['no_of_containers'] = $locationDetails[$locationId]['no_of_containers'] ?? 0;
                    $item['description'] = $locationDetails[$locationId]['description'] ?? null;
                    $item['location_instruction'] = $locationDetails[$locationId]['location_instruction'] ?? null;
                }

                // Calculate totals from sub-items
                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) {
                        return ($subItem['no_of_bags'] ?? 0) * ($subItem['bag_size'] ?? 0);
                    });

                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = $totalKgsFromSubItems;
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                }

                // Store bag_type_id as JSON array
                if (isset($item['bag_type_id']) && is_array($item['bag_type_id'])) {
                    $item['bag_type_id'] = json_encode($item['bag_type_id']);
                }

                // Create packing item
                $packingItem = $jobOrder->packingItems()->create($item);

                // Create sub-items
                if (!empty($subItems)) {
                    foreach ($subItems as $subItem) {
                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            $jobOrder->specifications()->delete();
            foreach ($request->specifications as $spec) {
                $jobOrder->specifications()->create([
                    'product_slab_type_id' => $spec['product_slab_type_id'],
                    'spec_name' => $spec['spec_name'],
                    'spec_value' => $spec['spec_value'],
                    'uom' => $spec['uom'],
                    'value_type' => $spec['value_type']
                ]);
            }

            // Handle Container Protection & Packing Materials
            if ($request->has('container_protection_items') && !empty($request->container_protection_items)) {
                $containerProtectionData = [];
                foreach ($request->container_protection_items as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_per_container'])) {
                        $containerProtectionData[$item['product_id']] = [
                            'quantity_per_container' => $item['quantity_per_container'] ?? 0
                        ];
                    }
                }
                // Sync will update existing or create new, and remove ones not in the array
                $jobOrder->containerProtectionItems()->sync($containerProtectionData);
            } else {
                // If no items provided, remove all
                $jobOrder->containerProtectionItems()->sync([]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order updated successfully.',
                'data' => $jobOrder
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatebk17Nov(JobOrderRequest $request, JobOrder $jobOrder)
    {
        // Update main job order data
        $jobOrderData = $request->only([
            'job_order_no',
            'job_order_date',
            'ref_no',
            'product_id',
            'remarks',
            'order_description',
            'delivery_date',
            'loading_date',
            'packing_description',
            'crop_year_id',
            'other_specifications',
        ]);

        // JSON data update karein
        $jobOrderData['location'] = $request->location;
        $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
        $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
        $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
        $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

        $jobOrder->update($jobOrderData);

        // Update packing items - delete old and create new
        $jobOrder->packingItems()->delete();
        foreach ($request->packing_items as $item) {
            $jobOrder->packingItems()->create($item);
        }

        // Update specifications - delete old and create new
        $jobOrder->specifications()->delete();
        foreach ($request->specifications as $spec) {

            $jobOrder->specifications()->create([
                'product_slab_type_id' => $spec['product_slab_type_id'],
                'spec_name' => $spec['spec_name'],
                'spec_value' => $spec['spec_value'],
                'uom' => $spec['uom'],
                'value_type' => $spec['value_type']
            ]);
        }

        return response()->json([
            'success' => 'Job Order updated successfully.',
            'data' => $jobOrder
        ], 200);
    }

    public function updatebk(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'job_order_no' => 'required|unique:job_orders,job_order_no,' . $jobOrder->id,
            'job_order_date' => 'required|date',
            'location' => 'required',
            'product_id' => 'required|exists:products,id',
            'packing_items' => 'required|array|min:1'
        ]);

        // Update main job order data
        $jobOrderData = $request->only([
            'job_order_no',
            'job_order_date',
            'location',
            'ref_no',
            'attention_to',
            'product_id',
            'remarks',
            'order_description',
            'inspection_company_id',
            'fumigation_company_id',
            'delivery_date',
            'loading_date',
            'packing_description'
        ]);

        $jobOrderData['arrival_locations'] = $request->arrival_locations;

        $jobOrder->update($jobOrderData);

        // Update packing items - delete old and create new
        $jobOrder->packingItems()->delete();
        foreach ($request->packing_items as $item) {
            $jobOrder->packingItems()->create($item);
        }

        return response()->json([
            'success' => 'Job Order updated successfully.',
            'data' => $jobOrder
        ], 200);
    }

    public function destroy($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $jobOrder->delete();

        return response()->json([
            'success' => 'Job Order deleted successfully.'
        ], 200);
    }

    // Get product specifications for selected product
    public function getProductSpecsbk($productId)
    {
        $specs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get()
            ->map(function ($slab) {
                return [
                    'spec_name' => $slab->slabType->name ?? '',
                    'spec_value' => $slab->deduction_value,
                    'uom' => $slab->slabType->uom ?? ''
                ];
            });

        return response()->json($specs);
    }



    public function getProductSpecs($productId)
    {
        $specs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($slabs) {
                // Pehla slab le rahe hain kyun ke har type ka ek hi slab hoga group mein
                $firstSlab = $slabs->first();
                return [
                    'id' => $firstSlab->slabType->id,
                    'spec_name' => $firstSlab->slabType->name ?? '',
                    'spec_value' => $firstSlab->deduction_value ?? 0,
                    'uom' => $firstSlab->slabType->qc_symbol ?? ''
                ];
            })
            ->values(); // Array keys reset karega

        return view('management.production.job_orders.partials.product_specs', compact('specs'));
    }
}