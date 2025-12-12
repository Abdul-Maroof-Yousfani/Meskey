<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\ProductionVoucherRequest;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionInput;
use App\Models\Production\ProductionOutput;
use App\Models\Product;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Brands;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionVoucherController extends Controller
{
    public function index()
    {
        return view('management.production.production_voucher.index');
    }

    public function getList(Request $request)
    {
        $query = ProductionVoucher::with([
            'jobOrder',
            'location',
            'supervisor'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prod_no', 'like', '%' . $search . '%')
                    ->orWhereHas('jobOrder', function ($q) use ($search) {
                        $q->where('job_order_no', 'like', '%' . $search . '%')
                            ->orWhere('ref_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('supervisor', function ($q) use ($search) {
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
            $query->whereDate('prod_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('prod_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'prod_date');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['prod_no', 'prod_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('prod_date', 'desc')->orderBy('created_at', 'desc');
        }

        // Paginate results
        $productionVouchers = $query->paginate(request('per_page', 25));

        // Get job orders for filter dropdown
        $jobOrders = JobOrder::where('status', 1)
            ->orderBy('job_order_no', 'desc')
            ->get();

        // Return view with data
        return view('management.production.production_voucher.getList', compact(
            'productionVouchers',
            'jobOrders'
        ));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $supervisors = User::where('status', 1)->get();

        return view('management.production.production_voucher.create', compact(
            'companyLocations',
            'supervisors'
        ));
    }

    public function getJobOrdersByLocation(Request $request)
    {
        $locationId = $request->location_id;
        
        if (!$locationId) {
            return response()->json(['jobOrders' => []]);
        }

        $user = auth()->user();

        $jobOrders = JobOrder::with('product')
            ->where('status', 1)
            ->whereHas('packingItems', function ($q) use ($locationId) {
                $q->where('company_location_id', $locationId);
            })
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->whereHas('packingItems', function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id);
                });
            })
            ->get()
            ->map(function ($jobOrder) {
                return [
                    'id' => $jobOrder->id,
                    'job_order_no' => $jobOrder->job_order_no,
                    'ref_no' => $jobOrder->ref_no,
                    'product_name' => $jobOrder->product->name ?? 'N/A'
                ];
            });

        return response()->json(['jobOrders' => $jobOrders]);
    }

    public function store(ProductionVoucherRequest $request)
    {
        DB::beginTransaction();

        try {
            $uniqueProdNo = generateUniversalUniqueNo('production_vouchers', [
                'prefix' => 'PRO',
                'column' => 'prod_no',
                'with_date' => 1,
                'custom_date' => $request->prod_date,
                'date_format' => 'm-Y',
                'serial_at_end' => 1,
            ]);

            $productionVoucherData = $request->only([
                'prod_date',
                'location_id',
                'produced_qty_kg',
                'supervisor_id',
                'labor_cost_per_kg',
                'overhead_cost_per_kg',
                'status',
                'remarks'
            ]);

            // Handle multiple job orders - take first one or store as JSON
            $jobOrderIds = is_array($request->job_order_id) ? $request->job_order_id : [$request->job_order_id];
            $productionVoucherData['job_order_id'] = $jobOrderIds[0]; // Store first job order ID

            $productionVoucherData['company_id'] = $request->company_id;
            $productionVoucherData['prod_no'] = $uniqueProdNo;

            $productionVoucher = ProductionVoucher::create($productionVoucherData);

            DB::commit();

            return response()->json([
                'success' => 'Production Voucher created successfully.',
                'redirect' => route('production-voucher.edit', $productionVoucher->id),
                'data' => $productionVoucher
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
        $productionVoucher = ProductionVoucher::with([
            'jobOrder',
            'location',
            'supervisor',
            'inputs.product',
            'inputs.location',
            'outputs.product',
            'outputs.storageLocation',
            'outputs.brand'
        ])->findOrFail($id);

        $jobOrders = JobOrder::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $supervisors = User::where('status', 1)->get();
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();

        return view('management.production.production_voucher.edit', compact(
            'productionVoucher',
            'jobOrders',
            'companyLocations',
            'supervisors',
            'products',
            'sublocations',
            'brands'
        ));
    }

    public function update(ProductionVoucherRequest $request, $id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);

        DB::beginTransaction();

        try {
            $productionVoucherData = $request->only([
                'prod_date',
                'job_order_id',
                'location_id',
                'produced_qty_kg',
                'supervisor_id',
                'labor_cost_per_kg',
                'overhead_cost_per_kg',
                'status',
                'remarks'
            ]);

            $productionVoucher->update($productionVoucherData);

            DB::commit();

            return response()->json([
                'success' => 'Production Voucher updated successfully.',
                'data' => $productionVoucher
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $productionVoucher->delete();

        return response()->json([
            'success' => 'Production Voucher deleted successfully.'
        ], 200);
    }

    // Production Input Form
    public function getInputForm($id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();

        return view('management.production.production_voucher.partials.production_input_form', compact(
            'productionVoucher',
            'products',
            'sublocations'
        ));
    }

    // Production Output Form
    public function getOutputForm($id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $brands = Brands::where('status', 1)->get();

        return view('management.production.production_voucher.partials.production_output_form', compact(
            'productionVoucher',
            'products',
            'companyLocations',
            'brands'
        ));
    }

    // Production Input Methods
    public function storeInput(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:arrival_sub_locations,id',
            'qty' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $productionVoucher = ProductionVoucher::findOrFail($id);

        $input = ProductionInput::create([
            'production_voucher_id' => $productionVoucher->id,
            'product_id' => $request->product_id,
            'location_id' => $request->location_id,
            'qty' => $request->qty,
            'remarks' => $request->remarks
        ]);

        return response()->json([
            'success' => 'Production Input added successfully.',
            'data' => $input->load('product', 'location')
        ], 201);
    }

    public function updateInput(Request $request, $id, $inputId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:arrival_sub_locations,id',
            'qty' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $input = ProductionInput::where('production_voucher_id', $id)
            ->findOrFail($inputId);

        $input->update($request->only(['product_id', 'location_id', 'qty', 'remarks']));

        return response()->json([
            'success' => 'Production Input updated successfully.',
            'data' => $input->load('product', 'location')
        ], 200);
    }

    public function destroyInput($id, $inputId)
    {
        $input = ProductionInput::where('production_voucher_id', $id)
            ->findOrFail($inputId);
        $input->delete();

        return response()->json([
            'success' => 'Production Input deleted successfully.'
        ], 200);
    }

    // Production Output Methods
    public function storeOutput(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'storage_location_id' => 'required|exists:company_locations,id',
            'brand_id' => 'nullable|exists:brands,id',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $productionVoucher = ProductionVoucher::findOrFail($id);

        $output = ProductionOutput::create([
            'production_voucher_id' => $productionVoucher->id,
            'product_id' => $request->product_id,
            'qty' => $request->qty,
            'storage_location_id' => $request->storage_location_id,
            'brand_id' => $request->brand_id,
            'remarks' => $request->remarks
        ]);

        return response()->json([
            'success' => 'Production Output added successfully.',
            'data' => $output->load('product', 'storageLocation', 'brand')
        ], 201);
    }

    public function updateOutput(Request $request, $id, $outputId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'storage_location_id' => 'required|exists:company_locations,id',
            'brand_id' => 'nullable|exists:brands,id',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $output = ProductionOutput::where('production_voucher_id', $id)
            ->findOrFail($outputId);

        $output->update($request->only(['product_id', 'qty', 'storage_location_id', 'brand_id', 'remarks']));

        return response()->json([
            'success' => 'Production Output updated successfully.',
            'data' => $output->load('product', 'storageLocation', 'brand')
        ], 200);
    }

    public function destroyOutput($id, $outputId)
    {
        $output = ProductionOutput::where('production_voucher_id', $id)
            ->findOrFail($outputId);
        $output->delete();

        return response()->json([
            'success' => 'Production Output deleted successfully.'
        ], 200);
    }
}
