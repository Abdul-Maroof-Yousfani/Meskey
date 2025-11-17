<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobOrderRequest;
use App\Models\Master\CropYear;
use App\Models\Production\JobOrder\{
    JobOrderPackingItem,
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
            'users',
            'cropYears'
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
                'location' => $locationCode,
                'column' => 'job_order_no',
                'with_date' => 1,
                'custom_date' => $request->job_order_date,
                'date_format' => 'm-Y',
                'serial_at_end' => 1,
            ]);

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

            $jobOrderData['company_id'] = $request->company_id;
            $jobOrderData['job_order_no'] = $uniqueJobNo;
            $jobOrderData['company_location_id'] = $request->company_location_id;
            $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
            $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
            $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

            $jobOrder = JobOrder::create($jobOrderData);

            foreach ($request->packing_items as $item) {
                $jobOrder->packingItems()->create($item);
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

    public function edit($id)
    {
        $jobOrder = JobOrder::with(['packingItems', 'specifications', 'product'])->findOrFail($id);

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
            'users',
            'cropYears'
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
                'delivery_date',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
            ]);

            $jobOrderData['location'] = $request->location;
            $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
            $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
            $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

            $jobOrder->update($jobOrderData);

            $jobOrder->packingItems()->delete();
            foreach ($request->packing_items as $item) {
                $jobOrder->packingItems()->create($item);
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