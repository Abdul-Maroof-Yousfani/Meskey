<?php

namespace App\Http\Controllers\Production;


use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalLocation;
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
use Carbon\Carbon;
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

    public function create(Request $request)
    {
        $user = auth()->user();
        $selectedDate = $request->input('qc_date', $request->input('date', date('Y-m-d')));

        $jobOrders = JobOrder::with(['product', 'companyLocation'])
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->whereDate('job_order_date', $selectedDate)
            ->when($user && $user->user_type !== 'super-admin' && $user->company_location_id, function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id)
                        ->orWhereHas('packingItems', function ($pi) use ($user) {
                            $pi->where('company_location_id', $user->company_location_id);
                        });
                });
            })
            ->latest()
            ->get();

        $products = Product::where('status', 1)->get();

        $locationIds = getUserCurrentCompanyLocations();
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }
        $selectedLocation = count($companyLocations) == 1 ? $companyLocations->first() : ($user?->companyLocation ?? null);
        if (!$selectedLocation && count($companyLocations) > 0) {
            $selectedLocation = $companyLocations->first();
        }

        $qcNo = self::generateUniqueNumber($selectedDate, $selectedLocation?->id);
        $qcParameters = [];

        return view('management.production.job_order_raw_material_qc.create', compact(
            'jobOrders',
            'products',
            'qcParameters',
            'qcNo',
            'selectedDate'
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
        $locations = ArrivalLocation::where('company_location_id',$request->company_location_id)->where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('company_location_id',$request->company_location_id)->where('status', 1)->get();

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
            'locations',
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


            $qcNo = $request->qc_no;
            if (empty($qcNo) || JobOrderRawMaterialQc::withTrashed()->where('qc_no', $qcNo)->exists()) {
                $qcNo = self::generateUniqueNumber($request->qc_date, $request->company_location_id);
            }

            // Create QC record
            $qc = JobOrderRawMaterialQc::create([
                'company_id' => $request->company_id ?? auth()->user()?->company_id ?? 1,
                'qc_no' => $qcNo,
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
                        'arrival_location_id' => $locationData['location_id'],
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
                'success' => 'Raw Material QC created successfully.',
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


        
        $selectedDate = $qc->qc_date ? $qc->qc_date->format('Y-m-d') : date('Y-m-d');
        $jobOrders = JobOrder::where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->where(function ($q) use ($selectedDate, $qc) {
                $q->whereDate('job_order_date', $selectedDate)
                  ->orWhere('id', $qc->job_order_id);
            })
            ->get();
        $companyLocations = CompanyLocation::where('status', 1)->get();
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('company_location_id',$qc->location_id)->where('status', 1)->get();
        $locations = ArrivalLocation::where('company_location_id',$qc->location_id)->where('status', 1)->get();

        return view('management.production.job_order_raw_material_qc.edit', compact(
            'qc',
            'jobOrders',
            'companyLocations',
            'products',
            'locations',
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
            // dd($productData['locations']);
            foreach ($productData['locations'] as $locationIndex => $locationData) {
                // Create QC item
                // dd($locationData);
                $qcItem = JobOrderRawMaterialQcItem::create([
                    'job_order_rm_qc_id' => $jobOrderRawMaterialQc->id,
                    'product_id' => $productId,
                    'arrival_location_id' => $locationData['location_id'],
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

    public static function generateUniqueNumber($dateParam = null, $locationParam = null)
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        if (empty($locationParam)) {
            $locationParam = request('company_location_id') ?? request('location_id') ?? auth()->user()?->company_location_id;
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
        } else {
            $userLocations = function_exists('getUserCurrentCompanyLocations') ? getUserCurrentCompanyLocations() : [];
            if (!empty($userLocations)) {
                $firstLoc = CompanyLocation::find($userLocations[0]);
                if ($firstLoc && $firstLoc->code) {
                    $locationCode = $firstLoc->code;
                }
            }
            if ($locationCode === 'LOC') {
                $firstLoc = CompanyLocation::where('status', 'active')->first();
                if ($firstLoc && $firstLoc->code) {
                    $locationCode = $firstLoc->code;
                }
            }
        }

        $locationCode = strtoupper(trim($locationCode ?: 'LOC'));
        $prefix = $locationCode . '-JORMQC-MIL';

        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = JobOrderRawMaterialQc::withTrashed()
            ->where('qc_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('qc_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || JobOrderRawMaterialQc::withTrashed()->where('qc_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    public function getNumber(Request $request)
    {
        $dateParam = $request->qc_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->company_location_id ?? $request->location_id ?? null;
        $qcNo = self::generateUniqueNumber($dateParam, $locationParam);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'qc_no' => $qcNo
            ]);
        }

        return $qcNo;
    }

    public function getJobOrdersByDate(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', $request->input('qc_date', date('Y-m-d')));

        $jobOrders = JobOrder::with(['product', 'companyLocation'])
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->whereDate('job_order_date', $date)
            ->when($user && $user->user_type !== 'super-admin' && $user->company_location_id, function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id)
                        ->orWhereHas('packingItems', function ($pi) use ($user) {
                            $pi->where('company_location_id', $user->company_location_id);
                        });
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'job_orders' => $jobOrders->map(function ($jobOrder) {
                return [
                    'id' => $jobOrder->id,
                    'job_order_no' => $jobOrder->job_order_no,
                    'company_location_id' => $jobOrder->company_location_id,
                    'location_ids' => $jobOrder->company_locations->pluck('id')->toArray(),
                    'product_name' => $jobOrder->product?->name ?? 'N/A'
                ];
            })
        ]);
    }
}