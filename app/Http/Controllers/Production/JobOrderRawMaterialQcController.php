<?php

namespace App\Http\Controllers\Production;


use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder\{
    JobOrder,
    JobOrderRawMaterialQc,
    JobOrderRawMaterialQcItem,
    JobOrderRawMaterialQcParameter
};
use App\Models\Product;
use App\Models\Master\{
    ProductSlab,
    CompanyLocation,
    ArrivalSubLocation
};

use Illuminate\Http\Request;
use App\Http\Requests\Production\JobOrderRawMaterialQcRequest;
use Illuminate\Support\Facades\DB;
class JobOrderRawMaterialQcController extends Controller
{
    public function index()
    {
        return view('management.production.job_order_raw_material_qc.index');
    }

    public function getList(Request $request)
    {
        // Start query with relationships
        $query = JobOrderRawMaterialQc::with([
            'jobOrder',
            'location',
            'items.product'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('qc_no', 'like', '%' . $search . '%')
                    ->orWhere('mill', 'like', '%' . $search . '%')
                    ->orWhereHas('jobOrder', function ($q) use ($search) {
                        $q->where('job_order_no', 'like', '%' . $search . '%')
                            ->orWhere('ref_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Filter by job order
        if ($request->filled('job_order_id')) {
            $query->where('job_order_id', $request->job_order_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('qc_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('qc_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by mill
        if ($request->filled('mill')) {
            $query->where('mill', 'like', '%' . $request->mill . '%');
        }

        // Filter by commodity
        if ($request->filled('commodity_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->commodity_id);
            });
        }

        // Get job orders for filter dropdown
        $jobOrders = JobOrder::where('status', 1)
            ->orderBy('job_order_no', 'desc')
            ->get();

        // Apply sorting
        $sortField = $request->get('sort', 'qc_date');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['qc_no', 'qc_date', 'mill'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('qc_date', 'desc')->orderBy('created_at', 'desc');
        }

        // Paginate results
        $qcs = $query->paginate(request('per_page', 25))->withQueryString();

        // Return view with data
        return view('management.production.job_order_raw_material_qc.getList', compact(
            'qcs',
            'jobOrders'
        ));
    }

    public function create()
    {
        $user = auth()->user();

        $jobOrders = JobOrder::with('product')
            ->where('status', 1)
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->whereHas('packingItems', function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id);
                });
            })
            ->get();

        $products = Product::where('status', 1)->get();

        $qcParameters = [];
        // $qcParameters = [
        //     'Broken %',
        //     'Chalky %',
        //     'Damaged %',
        //     'Moisture %',
        //     'Yellow Kernels %',
        //     'Foreign Matter %'
        // ];

        return view('management.production.job_order_raw_material_qc.create', compact(
            'jobOrders',
            // 'companyLocations',
            'products',
            'qcParameters'
        ));
    }

    public function getJobOrderDetails(Request $request)
    {
        // dd($request);
        // $companyLocations = CompanyLocation::where('status', 1)->get();
        $jobOrder = JobOrder::with(['product'])->findOrFail($request->job_order_id);

        $user = auth()->user();
        $companyLocations = $jobOrder->company_locations
            ->when($user->user_type !== 'super-admin', function ($collection) use ($user) {
                return $collection->filter(function ($location) use ($user) {
                    return $location->id == $user->company_location_id;
                });
            });

        $products = Product::where('status', 1)->get();

        return view('management.production.job_order_raw_material_qc.partials.job_order_detail', compact(
            'companyLocations',
            'jobOrder',
            'products',
            // 'sublocations',
            // 'commodityParameters'
        ));
    }

    public function loadQcCommoditiesTables(Request $request)
    {

        $commodities = $request->get('commodities', []);

        $products = Product::whereIn('id', $commodities)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();

        // Get QC parameters for each commodity separately
        $commodityParameters = [];

        foreach ($commodities as $commodityId) {
            $specs = ProductSlab::with('slabType')
                ->where('product_id', $commodityId)
                ->where('status', 1)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($slabs) {
                    $firstSlab = $slabs->first();
                    return [
                        'id' => $firstSlab->slabType->id,
                        'spec_name' => $firstSlab->slabType->name ?? '',
                        'spec_value' => $firstSlab->deduction_value ?? 0,
                        'uom' => $firstSlab->slabType->qc_symbol ?? ''
                    ];
                })
                ->values();

            // Extract parameter names
            $parameters = $specs->pluck('spec_name')->toArray();

            $commodityParameters[$commodityId] = [
                'parameters' => $parameters,
                'specs_data' => $specs
            ];
            // dd($commodityParameters);
            // If no parameters found for this commodity, use default
            if (empty($commodityParameters[$commodityId]['parameters'])) {
                $commodityParameters[$commodityId]['parameters'] = [

                ];
            }
        }

        return view('management.production.job_order_raw_material_qc.partials.qc_commodities_tables', compact(
            'commodities',
            'products',
            'sublocations',
            'commodityParameters'
        ));
    }

    public function store(JobOrderRawMaterialQcRequest $request)
    {

        // dd($request->all());
        // $request->validate([
        //     'qc_no' => 'required|unique:job_order_raw_material_qcs',
        //     'qc_date' => 'required|date',
        //     'job_order_id' => 'required|exists:job_orders,id',
        //     'company_location_id' => 'required|exists:company_locations,id',
        //     // 'mill' => 'required|string',
        //     'commodities' => 'required|array|min:1',
        //     'commodities.*' => 'exists:products,id',
        //     'qc_data' => 'required|array'
        // ]);
        DB::beginTransaction();

        try {


            // Create QC record
            $qc = JobOrderRawMaterialQc::create([
                'company_id' => $request->company_id,
                'qc_no' => $request->qc_no,
                'qc_date' => $request->qc_date,
                'job_order_id' => $request->job_order_id,
                'location_id' => $request->company_location_id,
                'mill' => $request->mill,
                'commodities' => json_encode($request->commodities)
            ]);

            // Create QC items and parameters
            foreach ($request->qc_data as $productId => $productData) {
                foreach ($productData['locations'] as $locationIndex => $locationData) {
                    // Create QC item
                    $qcItem = JobOrderRawMaterialQcItem::create([
                        'job_order_rm_qc_id' => $qc->id,
                        'product_id' => $productId,
                        'arrival_sub_location_id' => $locationData['sublocation_id'],
                        'suggested_quantity' => $locationData['suggested_quantity']
                    ]);

                    // Create parameters for this item
                    foreach ($locationData['parameters'] as $paramName => $paramValue) {
                        if (!empty($paramValue)) {
                            JobOrderRawMaterialQcParameter::create([
                                'job_order_qc_item_id' => $qcItem->id,
                                'product_slab_type_id' => $paramName,
                                'parameter_name' => $paramName,
                                'parameter_value' => $paramValue,
                                'uom' => '%'
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order created successfully.',
                'data' => []
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }


    public function edit($id)
    {
        $qc = JobOrderRawMaterialQc::with([
            'items',
            'items.parameters',
            'items.product',
            'items.sublocation',
            'jobOrder',
            'location'
        ])->findOrFail($id);

        $jobOrders = JobOrder::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 1)->get();
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();

        return view('management.production.job_order_raw_material_qc.edit', compact(
            'qc',
            'jobOrders',
            'companyLocations',
            'products',
            'sublocations'
        ));
    }

    public function update(JobOrderRawMaterialQcRequest $request, $id)
    {
        $jobOrderRawMaterialQc = JobOrderRawMaterialQc::findorfail($id);
        // $request->validate([
        //     'qc_no' => 'required|unique:job_order_raw_material_qcs,qc_no,' . $jobOrderRawMaterialQc->id,
        //     'qc_date' => 'required|date',
        //     'job_order_id' => 'required|exists:job_orders,id',
        //     'company_location_id' => 'required|exists:company_locations,id',
        //     // 'mill' => 'required|string',
        //     'commodities' => 'required|array|min:1',
        //     'commodities.*' => 'exists:products,id',
        //     'qc_data' => 'required|array'
        // ]);

        // Update QC record
        $jobOrderRawMaterialQc->update([
            'qc_no' => $request->qc_no,
            'qc_date' => $request->qc_date,
            'job_order_id' => $request->job_order_id,
            'location_id' => $request->company_location_id,
            'mill' => $request->mill,
            'commodities' => json_encode($request->commodities)
        ]);

        // Delete old items and parameters
        $jobOrderRawMaterialQc->items()->delete();

        // Create new QC items and parameters
        foreach ($request->qc_data as $productId => $productData) {
            foreach ($productData['locations'] as $locationIndex => $locationData) {
                // Create QC item
                $qcItem = JobOrderRawMaterialQcItem::create([
                    'job_order_rm_qc_id' => $jobOrderRawMaterialQc->id,
                    'product_id' => $productId,
                    'arrival_sub_location_id' => $locationData['sublocation_id'],
                    'suggested_quantity' => $locationData['suggested_quantity']
                ]);

                // Create parameters for this item
                foreach ($locationData['parameters'] as $paramName => $paramValue) {
                    if (!empty($paramValue)) {
                        JobOrderRawMaterialQcParameter::create([
                            'job_order_qc_item_id' => $qcItem->id,
                            'parameter_name' => $paramName,
                            'product_slab_type_id' => $paramName,
                            'parameter_value' => $paramValue,
                            'uom' => '%'
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => 'Raw Material QC updated successfully.',
            'data' => $jobOrderRawMaterialQc
        ], 200);
    }
    public function editbk($id)
    {
        $qc = JobOrderRawMaterialQc::with(['items', 'items.parameters', 'items.product', 'items.sublocation'])->findOrFail($id);

        $jobOrders = JobOrder::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 1)->get();
        $products = Product::where('status', 1)->get();

        $qcParameters = [
            'Broken %',
            'Chalky %',
            'Damaged %',
            'Moisture %',
            'Yellow Kernels %',
            'Foreign Matter %'
        ];

        return view('management.job_order_raw_material_qc.edit', compact(
            'qc',
            'jobOrders',
            'companyLocations',
            'products',
            'qcParameters'
        ));
    }

    public function updatebk(Request $request, JobOrderRawMaterialQc $jobOrderRawMaterialQc)
    {
        $request->validate([
            'qc_no' => 'required|unique:job_order_raw_material_qcs,qc_no,' . $jobOrderRawMaterialQc->id,
            'qc_date' => 'required|date',
            'job_order_id' => 'required|exists:job_orders,id',
            'location_id' => 'required|exists:company_locations,id',
            'mill' => 'required|string',
            'commodities' => 'required|array|min:1',
            'commodities.*' => 'exists:products,id',
            'qc_data' => 'required|array'
        ]);

        // Update QC record
        $jobOrderRawMaterialQc->update([
            'qc_no' => $request->qc_no,
            'qc_date' => $request->qc_date,
            'job_order_id' => $request->job_order_id,
            'location_id' => $request->location_id,
            'mill' => $request->mill,
            'commodities' => json_encode($request->commodities)
        ]);

        // Delete old items and parameters
        $jobOrderRawMaterialQc->items()->delete();

        // Create new QC items and parameters
        foreach ($request->qc_data as $productId => $productData) {
            foreach ($productData['locations'] as $locationIndex => $locationData) {
                // Create QC item
                $qcItem = JobOrderRawMaterialQcItem::create([
                    'job_order_raw_material_qc_id' => $jobOrderRawMaterialQc->id,
                    'product_id' => $productId,
                    'arrival_sublocation_id' => $locationData['sublocation_id'],
                    'suggested_quantity' => $locationData['suggested_quantity']
                ]);

                // Create parameters for this item
                foreach ($locationData['parameters'] as $paramName => $paramValue) {
                    if (!empty($paramValue)) {
                        JobOrderRawMaterialQcParameter::create([
                            'job_order_raw_material_qc_item_id' => $qcItem->id,
                            'parameter_name' => $paramName,
                            'parameter_value' => $paramValue,
                            'uom' => '%'
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => 'Raw Material QC updated successfully.',
            'data' => $jobOrderRawMaterialQc
        ], 200);
    }

    public function destroy($id)
    {
        $qc = JobOrderRawMaterialQc::findOrFail($id);
        $qc->delete();

        return response()->json([
            'success' => 'Raw Material QC deleted successfully.'
        ], 200);
    }
}