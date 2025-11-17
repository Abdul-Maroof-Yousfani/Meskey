<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobOrderRequest;
use App\Models\Production\JobOrder\{
    JobOrderPackingItem,
    JobOrder,
    JobOrderSpecification
};
use App\Models\Master\{InspectionCompany, FumigationCompany, CompanyLocation, ProductSlab, ArrivalLocation, Brands, Color};
use App\Models\{Product, BagCondition, BagType};
use Illuminate\Http\Request;
use App\Models\User;
class JobOrderController extends Controller
{
    public function index()
    {
        return view('management.production.job_orders.index');
    }

    public function getList(Request $request)
    {
        $job_orders = JobOrder::with(['product', 'companyLocation'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('job_order_no', 'like', $searchTerm)
                        ->orWhere('location', 'like', $searchTerm)
                        ->orWhere('ref_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.production.job_orders.getList', compact('job_orders'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->get();
        $inspectionCompanies = InspectionCompany::where('status', 1)->get();
        $fumigationCompanies = FumigationCompany::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // Users for attention_to

        return view('management.production.job_orders.create', compact(
            'products',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users'
        ));
    }

    public function store(JobOrderRequest $request)
    {



        $locationCode = CompanyLocation::where('id', $request->company_location_id)
            ->value('code');

        // 2) Isi waqt fresh unique number generate karo
        $uniqueJobNo = generateUniversalUniqueNo('job_orders', [
            'prefix' => 'JOB',
            'location' => $locationCode,
            'column' => 'job_order_no',
            // 'with_date' => 1,
            // 'custom_date' => $request->job_order_date,
            // 'date_format' => 'm-Y',
            // 'serial_at_end' => 1,
        ]);

        // Main job order data
        $jobOrderData = $request->only([
            'job_order_no',
            'job_order_date',
            'ref_no',
            'product_id',
            'remarks',
            'order_description',
            'delivery_date',
            'loading_date',
            'packing_description'
        ]);

        // JSON data store karein
        $jobOrderData['company_id'] = $request->company_id; // Single location
        $jobOrderData['job_order_no'] = $uniqueJobNo; // Single location
        $jobOrderData['company_location_id'] = $request->company_location_id; // Single location
        $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []); // Users array to JSON
        $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []); // Inspection companies array to JSON
        $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []); // Fumigation companies array to JSON
        $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []); // Arrival locations array to JSON


        $jobOrder = JobOrder::create($jobOrderData);

        // Create packing items
        foreach ($request->packing_items as $item) {
            $jobOrder->packingItems()->create($item);
        }

        // Create specifications
        foreach ($request->specifications as $spec) {
            $jobOrder->specifications()->create([
                'product_slab_id' => $spec['product_slab_id'],
                'spec_name' => $spec['spec_name'],
                'spec_value' => $spec['spec_value'],
                'uom' => $spec['uom']
            ]);
        }

        return response()->json([
            'success' => 'Job Order created successfully.',
            'data' => $jobOrder
        ], 201);
    }
    public function edit($id)
    {
        $jobOrder = JobOrder::with(['packingItems', 'specifications', 'product'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $inspectionCompanies = InspectionCompany::where('status', 1)->get();
        $fumigationCompanies = FumigationCompany::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::get();
        $users = User::get();
        // dd($bagColors);
        return view('management.production.job_orders.edit', compact(
            'jobOrder',
            'products',
            'inspectionCompanies',
            'fumigationCompanies',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users'
        ));
    }

    public function update(JobOrderRequest $request, JobOrder $jobOrder)
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
            'packing_description'
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
                'product_slab_id' => $spec['product_slab_id'],
                'spec_name' => $spec['spec_name'],
                'spec_value' => $spec['spec_value'],
                'uom' => $spec['uom']
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
                    'id' => $firstSlab->id,
                    'spec_name' => $firstSlab->slabType->name ?? '',
                    'spec_value' => $firstSlab->deduction_value ?? 0,
                    'uom' => $firstSlab->slabType->qc_symbol ?? ''
                ];
            })
            ->values(); // Array keys reset karega

        return view('management.production.job_orders.partials.product_specs', compact('specs'));
    }
}