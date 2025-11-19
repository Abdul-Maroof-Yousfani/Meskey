<?php

namespace App\Http\Controllers\Production;


use App\Http\Controllers\Controller;
use App\Models\Master\ProductSlab;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use App\Models\Production\JobOrder\JobOrderRawMaterialQcItem;
use App\Models\Production\JobOrder\JobOrderRawMaterialQcParameter;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Product;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalSublocation;
use Illuminate\Http\Request;

class JobOrderRawMaterialQcController extends Controller
{
    public function index()
    {
        return view('management.production.job_order_raw_material_qc.index');
    }

    public function getList(Request $request)
    {
        $qcs = JobOrderRawMaterialQc::with(['jobOrder', 'location'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('qc_no', 'like', $searchTerm)
                        ->orWhere('mill', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.production.job_order_raw_material_qc.getList', compact('qcs'));
    }

    public function create()
    {
        $jobOrders = JobOrder::with('product')->where('status', 1)->get();
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

        return view('management.production.job_order_raw_material_qc.create', compact(
            'jobOrders',
            'companyLocations',
            'products',
            'qcParameters'
        ));
    }

    public function getJobOrderDetails($jobOrderId)
    {

        $jobOrder = JobOrder::with(['product'])->findOrFail($jobOrderId);



        $arrivalLocationIds = json_decode($jobOrder->arrival_locations, true) ?? [];
        $sublocations = ArrivalSublocation::whereIn('arrival_location_id', $arrivalLocationIds)
            ->where('status', 1)
            ->get();

        return response()->json([
            'job_order' => $jobOrder,
            'sublocations' => $sublocations
        ]);
    }

    public function loadQcCommoditiesTables(Request $request)
    {
        $commodities = $request->get('commodities', []);
        
        $products = Product::whereIn('id', $commodities)->get();
        $sublocations = ArrivalSublocation::where('status', 1)->get();
        
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
            
            // If no parameters found for this commodity, use default
            if (empty($commodityParameters[$commodityId]['parameters'])) {
                $commodityParameters[$commodityId]['parameters'] = [
                    'Broken %',
                    'Chalky %', 
                    'Damaged %',
                    'Moisture %',
                    'Yellow Kernels %',
                    'Foreign Matter %'
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

    public function store(Request $request)
    {
        $request->validate([
            'qc_no' => 'required|unique:job_order_raw_material_qcs',
            'qc_date' => 'required|date',
            'job_order_id' => 'required|exists:job_orders,id',
            'location_id' => 'required|exists:company_locations,id',
            'mill' => 'required|string',
            'commodities' => 'required|array|min:1',
            'commodities.*' => 'exists:products,id',
            'qc_data' => 'required|array'
        ]);

        // Create QC record
        $qc = JobOrderRawMaterialQc::create([
            'qc_no' => $request->qc_no,
            'qc_date' => $request->qc_date,
            'job_order_id' => $request->job_order_id,
            'location_id' => $request->location_id,
            'mill' => $request->mill,
            'commodities' => json_encode($request->commodities)
        ]);

        // Create QC items and parameters
        foreach ($request->qc_data as $productId => $productData) {
            foreach ($productData['locations'] as $locationIndex => $locationData) {
                // Create QC item
                $qcItem = JobOrderRawMaterialQcItem::create([
                    'job_order_raw_material_qc_id' => $qc->id,
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
            'success' => 'Raw Material QC created successfully.',
            'data' => $qc
        ], 201);
    }

    public function edit($id)
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

    public function update(Request $request, JobOrderRawMaterialQc $jobOrderRawMaterialQc)
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