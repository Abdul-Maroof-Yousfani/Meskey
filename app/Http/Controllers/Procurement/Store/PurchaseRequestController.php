<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PurchaseRequest as ProcurementPurchaseRequest;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseAgainstJobOrder;
use App\Models\Procurement\Store\PurchaseItemApprove;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Master\Department;
use App\Models\Master\RequestBy;
use App\Models\Master\Color;
use App\Models\Master\Size;
use App\Models\Product;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $categories = Category::where('category_type', 'general_items')->get();
        $items = Product::where('product_type', 'general_items')->where('status', 'active')->get();
        return view('management.procurement.store.purchase_request.index', compact('categories', 'items'));
    }

    public function getItems(Request $request)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::with('packing_items.subItems')->where('id', request()->job_order)->get();
        $items = Product::with("unitOfMeasure")->where("product_type", "general_items")->where("status", "active")->get();
        $sizes = Size::all();

        $purchase_request_id = request()->purchase_request_id;
        return view('management.procurement.store.purchase_request.getItem', compact('job_orders', 'categories', 'items', 'purchase_request_id', 'sizes'));

    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $query = PurchaseRequestData::has('purchase_request')->with('purchase_request', 'category', 'item', 'approval', 'purchase_order_data')
            ->whereStatus(true);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qty', 'like', "%{$search}%")
                  ->orWhereHas('purchase_request', function($pr) use ($search) {
                      $pr->where('purchase_request_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('pr_no') && !empty($request->pr_no)) {
            $pr_no = $request->pr_no;
            $query->whereHas('purchase_request', function($pr) use ($pr_no) {
                $pr->where('purchase_request_no', 'like', "%{$pr_no}%");
            });
        }

        if ($request->has('date_range') && !empty($request->date_range)) {
            $parts = explode(' - ', $request->date_range);
            if (count($parts) == 2) {
                $from = trim($parts[0]);
                $to = trim($parts[1]);
                $query->whereHas('purchase_request', function($pr) use ($from, $to) {
                    $pr->whereDate('purchase_date', '>=', $from)
                       ->whereDate('purchase_date', '<=', $to);
                });
            }
        }

        if ($request->has('category_id') && $request->category_id != 'all' && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('qty') && !empty($request->qty)) {
            $query->where('qty', $request->qty);
        }

        if ($request->has('item_id') && $request->item_id != 'all' && !empty($request->item_id)) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->has('status') && $request->status != 'all' && !empty($request->status)) {
            $status = $request->status;
            $query->whereHas('purchase_request', function($pr) use ($status) {
                $pr->where('am_approval_status', $status);
            });
        }



        $PurchaseRequests = $query->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];
        foreach ($PurchaseRequests as $row) {
            $requestNo = $row->purchase_request?->purchase_request_no ?? 'unknown';

            if (!isset($groupedData[$requestNo])) {
                $groupedData[$requestNo] = [
                    'request_data' => $row->purchase_request,
                    'items' => [],           // yahan array of items
                ];
            }

            // Important: item_id ko key mat banao, normal push karo
            $groupedData[$requestNo]['items'][] = [
                'item_data' => $row,
            ];
        }

        foreach ($groupedData as $requestNo => $requestGroup) {
            $requestItems = [];
            $hasApprovedItem = false;

            foreach ($requestGroup['items'] as $itemGroup) {
                $approvalStatus = $itemGroup['item_data']
                    ?->{$itemGroup['item_data']->getApprovalModule()->approval_column ?? 'am_approval_status'};
                if (strtolower($approvalStatus) === 'approved') {
                    $hasApprovedItem = true;
                    break;
                }
            }

            $hasPendingItems = false;
            foreach ($requestGroup['items'] as $itemGroup) {
                if (in_array(strtolower($itemGroup['item_data']->am_approval_status), ['pending', 'reverted', 'returned'])) {
                    $hasPendingItems = true;
                    break;
                }
            }

            foreach ($requestGroup['items'] as $itemId => $itemGroup) {
                $requestItems[] = [
                    'item_data' => $itemGroup['item_data'],
                    'item_rowspan' => 1,
                ];
            }

            $requestRowspan = count($requestItems);

            $processedData[] = [
                'request_data' => $requestGroup['request_data'],
                'has_pending' => $hasPendingItems,
                'request_no' => $requestNo,
                'created_by_id' => $requestGroup['request_data']?->created_by,
                'request_status' => $requestGroup['request_data']?->am_approval_status,
                'request_rowspan' => $requestRowspan,
                'items' => $requestItems,
                'has_approved_item' => $hasApprovedItem,
            ];
        }

        return view('management.procurement.store.purchase_request.getList', [
            'PurchaseRequests' => $PurchaseRequests,
            'GroupedPurchaseRequests' => $processedData,
        ]);
    }

    public function approve($id)
    {

        PurchaseItemApprove::create([
            'status_id' => 2,
            'role_id' => Auth::id(),
            'purchase_request_data_id' => $id,
        ]);
    }

    public function create()
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::with(['packing_items', 'packing_items.subItems'])->get()
            ->reject(function ($job_order) {
                foreach ($job_order->packing_items as $packing) {
                    if (jobOrderPackingBalanceAgainstPurchaseRequest($packing->id) > 0) return false;
                    foreach ($packing->subItems as $subpacking) {
                        if (jobOrderSubPackingBalanceAgainstPurchaseRequest($subpacking->id) > 0) return false;
                    }
                }
                return true;
            });
        $items = Product::with("unitOfMeasure")->where("product_type", "general_items")->where("status", "active")->get();
        $departments = Department::where('status', 'active')->get();
        $request_bies = RequestBy::where('status', 'active')->get();
        $sizes = Size::all();

      
        return view('management.procurement.store.purchase_request.create', compact('categories', 'job_orders', 'items', 'departments', 'request_bies', 'sizes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProcurementPurchaseRequest $request)
    {
        DB::beginTransaction();
        try {
            $company_locations = $request->company_location_id;
            $purchaseRequest = PurchaseRequest::create([
                'purchase_request_no' => self::getNumber($request, $request->company_location_id, $request->purchase_date),
                'purchase_date' => $request->purchase_date,
                'location_id' => (CompanyLocation::first())->id,
                'company_id' => $request->company_id,
                'category_id' => $request->category_id_header,
                'department_id' => $request->department_id,
                'request_by_id' => $request->request_by_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
                'job_orders' => collect($request->master_job_orders)->flatten()->filter()
            ]);

            foreach ($company_locations as $company_location) {
                $purchaseRequest->locations()->create([
                    'location_id' => $company_location,
                ]);
            }

            $purchase_request_data_ids = [];
            foreach ($request->item_id as $index => $itemId) {
                $indexKey = $request->index[$index] ?? $index;
                $printingSamplePaths = [];

                if ($request->hasFile("printing_sample.$indexKey")) {
                    foreach ($request->file("printing_sample.$indexKey") as $file) {
                        $printingSamplePaths[] = $file->store('printing_samples', 'public');
                    }
                }

                // Handle stitching multi-select - convert array to comma-separated string
                $stitchingIndex = $request->index[$index] ?? $index;
                $stitchingValue = null;
                if (isset($request->stitching[$stitchingIndex])) {
                    $stitchingValue = is_array($request->stitching[$stitchingIndex]) 
                        ? implode(',', $request->stitching[$stitchingIndex]) 
                        : $request->stitching[$stitchingIndex];
                }
                $balance = null;
                if($request->module_type && $request->module_type[$index] == 'packing' && $request->packing_id && $request->packing_id[$index]) {
                    $balance = jobOrderPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                } else if($request->module_type && $request->module_type[$index] == 'subpacking' && $request->packing_id && $request->packing_id[$index]) {
                    $balance = jobOrderSubPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                }
                if(!is_null($balance) && $balance < $request->qty[$index]) {
                    DB::rollBack();
                    $validator = Validator::make([], []);
                    $validator->errors()->add(
                        "qty[$index]", "Your qty balance is $balance, you can not exceed that balance."
                    );
                    return response()->json([
                        "errors" => $validator->errors()
                    ], 422);
                }

                // Handle Dynamic Size Creation
                $sizeId = $request->size[$index] ?? null;
                if ($sizeId && !is_numeric($sizeId)) {
                    $newSize = Size::firstOrCreate(['size' => $sizeId], ['status' => 1, 'company_id' => $request->company_id]);
                    $sizeId = $newSize->id;
                }

                $requestData = PurchaseRequestData::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'category_id' => $request->category_id_header,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'approved_qty' => 0,
                    'min_weight' => $request->min_weight[$index] ?? null,
                    'color' => $request->color[$index] ?? null,
                    'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                    'size' => $sizeId,
                    'size_id' => $request->size_id[$index] ?? null,
                    'stitching' => $stitchingValue,
                    'micron' => $request->micron[$index] ?? null,
                    'printing_sample' => $printingSamplePaths,
                    'brand_id' => $request->brands[$index] ?? null,
                    'tolerance' => $request->tolerance[$index] ?? null,
                    'tolerance_percentage' => $request->tolerance_percentage[$index] ?? null,
                    'remarks' => $request->remarks[$index] ?? null,
                    'packing_id' => $request->packing_id[$index] ?? null,
                    "module_type" => $request->module_type[$index] ?? null,
                    "is_single_job_order" => $request->is_single_job_order[$index] ?? false

                ]);
                if (! empty($request->job_order_id[$request->index[$index]]) && is_array($request->job_order_id[$request->index[$index]])) {
                    foreach ($request->job_order_id[$request->index[$index]] as $job_order) {
                        PurchaseAgainstJobOrder::create([
                            'purchase_request_id' => $purchaseRequest->id,
                            'purchase_request_data_id' => $requestData->id,
                            'job_order_id' => $job_order,
                        ]);
                    }
                }
            }

            // if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
            //     foreach ($request->job_order_id[$index] as $jobOrderId) {

            //     }
            // }

            DB::commit();

            return response()->json([
                'success' => 'Purchase request created successfully.',
                'data' => $purchaseRequest,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase request. ',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $purchaseRequestData = PurchaseRequestData::findOrFail($id);
        $purchaseRequest = PurchaseRequest::with(['locations', 'PurchaseData', 'PurchaseData.JobOrder', 'PurchaseData.item.unitOfMeasure'])->where('id', $purchaseRequestData->purchase_request_id)->first();
        $locations_id = $purchaseRequest->locations->pluck('location_id')->toArray();
        $location_names = [];
        foreach ($locations_id as $location_id) {
            $location_names[] = get_location_name_by_id($location_id);
        }
        $items = Product::with("unitOfMeasure")->where("product_type", "general_items")->where("status", "active")->get();
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::with(['packing_items', 'packing_items.subItems'])->get()
            ->reject(function ($job_order) use ($purchaseRequest) {
                // If the JO is already associated with this PR, we should show it
                $isAssociated = PurchaseAgainstJobOrder::where('purchase_request_id', $purchaseRequest->id)
                    ->where('job_order_id', $job_order->id)
                    ->exists();


                if ($isAssociated) return false;

                $any_item_has_balance = false;
                foreach ($job_order->packing_items as $packing) {
                    if (jobOrderPackingBalanceAgainstPurchaseRequest($packing->id) > 0) {
                        $any_item_has_balance = true;
                        break;
                    }
                    foreach ($packing->subItems as $subpacking) {
                        if (jobOrderSubPackingBalanceAgainstPurchaseRequest($subpacking->id) > 0) {
                            $any_item_has_balance = true;
                            break;
                        }
                    }
                    if ($any_item_has_balance) break;
                }
                return !$any_item_has_balance;
            });
        $locations = CompanyLocation::all();
        $departments = Department::where('status', 'active')->get();
        $request_bies = RequestBy::where('status', 'active')->get();
        $sizes = Size::all();

        return view('management.procurement.store.purchase_request.edit', compact('items', 'locations_id', 'location_names', 'purchaseRequest', 'purchaseRequestData', 'categories', 'job_orders', 'locations', 'departments', 'request_bies', 'sizes'));
    }

    public function manageApprovals($id)
    {
        $purchaseRequestData = PurchaseRequestData::findOrFail($id);
        $purchaseRequest = PurchaseRequest::with(['locations', 'PurchaseData', 'PurchaseData.JobOrder', 'PurchaseData.item.unitOfMeasure'])->where('id', $purchaseRequestData->purchase_request_id)->first();
        $locations_id = $purchaseRequest->locations->pluck('location_id')->toArray();
        $location_names = [];
        foreach ($locations_id as $location_id) {
            $location_names[] = get_location_name_by_id($location_id);
        }
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $items = Product::with("unitOfMeasure")->where("product_type", "general_items")->where("status", "active")->get();

        $locations = CompanyLocation::all();
        $departments = Department::where('status', 'active')->get();
        $request_bies = RequestBy::where('status', 'active')->get();

        // dd($purchaseRequest);
        return view('management.procurement.store.purchase_request.approvalCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'data' => $purchaseRequest,
            'purchaseRequestData' => $purchaseRequestData,
            'categories' => $categories,
            'job_orders' => $job_orders,
            'locations' => $locations,
            'locations_id' => $locations_id,
            'items' => $items,
            'location_names' => $location_names,
            'departments' => $departments,
            'request_bies' => $request_bies,
            'sizes' => Size::all()
        ]);
    }

    public function update(ProcurementPurchaseRequest $request, $id)
    {
        DB::beginTransaction();
            
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);

            $updateData = [
                'purchase_date' => $request->purchase_date,
                'company_id' => $request->company_id,
                'category_id' => $request->category_id_header,
                'department_id' => $request->department_id,
                'request_by_id' => $request->request_by_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'am_change_made' => 1,
                'job_orders' => collect($request->master_job_orders)->flatten()->filter()
            ];
            // echo $purchaseRequest->am_approval_status;

            if ($purchaseRequest->am_approval_status == 'reverted') {
                $updateData['am_approval_status'] = 'pending';
            }

            $purchaseRequest->update($updateData);

            $existingItems = $purchaseRequest->PurchaseData->pluck('id')->toArray();
            $submittedItems = [];

            foreach ($request->item_id as $index => $itemId) {
                $printingSamplePath = null;
                if (! empty($request->item_row_id[$index])) {
                    $requestData = PurchaseRequestData::find($request->item_row_id[$index]);
                    $printingSamplePath = $requestData->printing_sample;

                    if ($requestData) {
                        $indexKeyUpdate = $request->index[$index] ?? $index;
                        if ($request->hasFile("printing_sample.$indexKeyUpdate")) {
                            $newFiles = [];
                            foreach ($request->file("printing_sample.$indexKeyUpdate") as $file) {
                                $newFiles[] = $file->store('printing_samples', 'public');
                            }
                            // Merge with existing files if any
                            $printingSamplePath = array_merge((array)$printingSamplePath, $newFiles);
                        }

                        // Handle stitching multi-select - convert array to comma-separated string
                        $stitchingIndexUpdate = $request->index[$index] ?? $index;
                        $stitchingValue = null;
                        if (isset($request->stitching[$stitchingIndexUpdate])) {
                            $stitchingValue = is_array($request->stitching[$stitchingIndexUpdate]) 
                                ? implode(',', $request->stitching[$stitchingIndexUpdate]) 
                                : $request->stitching[$stitchingIndexUpdate];
                        }

                        
                        $balance = null;
                        if($request->module_type && $request->module_type[$index] == 'packing' && $request->packing_id && $request->packing_id[$index]) {
                            $balance = jobOrderPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                        } else if($request->module_type && $request->module_type[$index] == 'subpacking' && $request->packing_id && $request->packing_id[$index]) {
                            $balance = jobOrderSubPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                        }
                        $current_qty = $request->current_qty[$index];
                        if(!is_null($balance) && ($balance + $current_qty) < $request->qty[$index]) {
                            DB::rollBack();
                            $validator = Validator::make([], []);
                            $remaining = $balance + $current_qty;
                            $validator->errors()->add(
                                "qty[$index]", "Your qty balance is {$remaining}, you can not exceed that balance."
                            );
                            return response()->json([
                                "errors" => $validator->errors()
                            ], 422);
                        }

                        // Handle Dynamic Size Creation
                        $sizeId = $request->size[$index] ?? null;
                        if ($sizeId && !is_numeric($sizeId)) {
                            $newSize = Size::firstOrCreate(['size' => $sizeId], ['status' => 1, 'company_id' => $request->company_id]);
                            $sizeId = $newSize->id;
                        }

                        $dataToUpdate = [
                            'category_id' => $request->category_id_header,
                            'item_id' => $itemId,
                            'qty' => $request->qty[$index],
                            'min_weight' => $request->min_weight[$index] ?? null,
                            'color' => $request->color[$index] ?? null,
                            'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                            'size' => $sizeId,
                            'size_id' => $request->size_id[$index] ?? null,
                            'stitching' => $stitchingValue,
                            'printing_sample' => $printingSamplePath,
                            'remarks' => $request->remarks[$index] ?? null,
                            'brand_id' => $request->brands[$index] ?? null,
                            'tolerance' => $request->tolerance[$index] ?? null,
                            'tolerance_percentage' => $request->tolerance_percentage[$index] ?? null,
                            'micron' => $request->micron[$index] ?? null,
                            'packing_id' => $request->packing_id[$index] ?? null,
                            "module_type" => $request->module_type[$index] ?? null,
                            "is_single_job_order" => $request->is_single_job_order[$index] ?? false
                        ];

                        if (in_array(strtolower($requestData->am_approval_status), ['reverted', 'returned'])) {
                            $dataToUpdate['am_approval_status'] = 'pending';
                        }

                        $requestData->update($dataToUpdate);
                        $submittedItems[] = $requestData->id;

                        PurchaseAgainstJobOrder::where('purchase_request_data_id', $requestData->id)->delete();
                        if (! empty($request->job_order_id[$request->index[$index]]) && is_array($request->job_order_id[$request->index[$index]])) {
                            foreach ($request->job_order_id[$request->index[$index]] as $jobOrderId) {
                                PurchaseAgainstJobOrder::create([
                                    'purchase_request_id' => $purchaseRequest->id,
                                    'purchase_request_data_id' => $requestData->id,
                                    'job_order_id' => $jobOrderId,
                                ]);
                            }
                        }
                    }
                } else {

                    $indexKeyNew = $request->index[$index] ?? $index;
                    $printingSamplePathNew = [];

                    if ($request->hasFile("printing_sample.$indexKeyNew")) {
                        foreach ($request->file("printing_sample.$indexKeyNew") as $file) {
                            $printingSamplePathNew[] = $file->store('printing_samples', 'public');
                        }
                    }

                    // Handle stitching multi-select - convert array to comma-separated string
                    $stitchingIndexNew = $request->index[$index] ?? $index;
                    $stitchingValueNew = null;
                    if (isset($request->stitching[$stitchingIndexNew])) {
                        $stitchingValueNew = is_array($request->stitching[$stitchingIndexNew]) 
                            ? implode(',', $request->stitching[$stitchingIndexNew]) 
                            : $request->stitching[$stitchingIndexNew];
                    }

                    $balance = null;
                    if($request->module_type && $request->module_type[$index] == 'packing' && $request->packing_id && $request->packing_id[$index]) {
                        $balance = jobOrderPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                    } else if($request->module_type && $request->module_type[$index] == 'subpacking' && $request->packing_id && $request->packing_id[$index]) {
                        $balance = jobOrderSubPackingBalanceAgainstPurchaseRequest($request->packing_id[$index]);
                    }
                    if(!is_null($balance) && $balance < $request->qty[$index]) {
                        DB::rollBack();
                        $validator = Validator::make([], []);
                        $validator->errors()->add(
                            "qty[$index]", "Your qty balance is $balance, you can not exceed that balance."
                        );
                        return response()->json([
                            "errors" => $validator->errors()
                        ], 422);
                    }

                    // Handle Dynamic Size Creation
                    $sizeIdNew = $request->size[$index] ?? null;
                    if ($sizeIdNew && !is_numeric($sizeIdNew)) {
                        $newSize = Size::firstOrCreate(['size' => $sizeIdNew], ['status' => 1, 'company_id' => $request->company_id]);
                        $sizeIdNew = $newSize->id;
                    }

                    $requestData = PurchaseRequestData::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'category_id' => $request->category_id_header,
                        'item_id' => $itemId,
                        'qty' => $request->qty[$index],
                        'approved_qty' => 0,
                        'min_weight' => $request->min_weight[$index] ?? null,
                        'color' => $request->color[$index] ?? null,
                        'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                        'size' => $sizeIdNew,
                        'size_id' => $request->size_id[$index] ?? null,
                        'stitching' => $stitchingValueNew,
                        'printing_sample' => $printingSamplePathNew,
                        'brand_id' => $request->brands[$index] ?? null,
                        'remarks' => $request->remarks[$index] ?? null,
                        'packing_id' => $request->packing_id[$index] ?? null,
                        "module_type" => $request->module_type[$index] ?? null,
                        "is_single_job_order" => $request->is_single_job_order[$index] ?? false,
                        'micron' => $request->micron[$index] ?? null,
                        'tolerance' => $request->tolerance[$index] ?? null,
                        'tolerance_percentage' => $request->tolerance_percentage[$index] ?? null,
                    ]);

                    $submittedItems[] = $requestData->id;
                    if (! empty($request->job_order_id[$request->index[$index]]) && is_array($request->job_order_id[$request->index[$index]])) {
                        foreach ($request->job_order_id[$request->index[$index]] as $jobOrderId) {
                            PurchaseAgainstJobOrder::create([
                                'purchase_request_id' => $purchaseRequest->id,
                                'purchase_request_data_id' => $requestData->id,
                                'job_order_id' => $jobOrderId,
                            ]);
                        }
                    }
                }
            }

            $itemsToDelete = array_diff($existingItems, $submittedItems);
            if (! empty($itemsToDelete)) {
                // IMPORTANT: Do NOT delete items that are already approved or rejected
                PurchaseRequestData::whereIn('id', $itemsToDelete)
                    ->whereNotIn('am_approval_status', ['approved', 'rejected'])
                    ->delete();
                
                // Also clean up job order associations only for the deleted items
                $actuallyDeletedIds = PurchaseRequestData::whereIn('id', $itemsToDelete)
                    ->whereNotIn('am_approval_status', ['approved', 'rejected'])
                    ->withTrashed() // If using SoftDeletes, or just use the same logic
                    ->pluck('id');
                
                PurchaseAgainstJobOrder::whereIn('purchase_request_data_id', $itemsToDelete)
                    ->whereHas('purchase_request_data', function($q) {
                        $q->whereNotIn('am_approval_status', ['approved', 'rejected']);
                    })
                    ->delete();
            }

            $purchaseRequest->syncStatusFromItems('System: PR updated');
            DB::commit();

            return response()->json([
                'success' => 'Purchase request updated successfully.',
                'data' => $purchaseRequest,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        
        // Find items that are NOT approved and NOT rejected
        $itemsToDelete = $purchaseRequest->PurchaseData()
            ->whereNotIn('am_approval_status', ['approved', 'rejected'])
            ->get();
        
        $deletedCount = 0;
        foreach ($itemsToDelete as $item) {
            $item->delete();
            $deletedCount++;
        }

        // Check if any items remain (approved or rejected items)
        $remainingCount = $purchaseRequest->PurchaseData()->count();

        if ($remainingCount == 0) {
            $purchaseRequest->delete();
            return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
        } else {
            $purchaseRequest->syncStatusFromItems('System: Non-approved/rejected items deleted');
            return response()->json(['success' => "Deleted $deletedCount pending items. Approved/Rejected items were preserved."], 200);
        }
    }

    public function destroyItem($id)
    {
        $item = PurchaseRequestData::findOrFail($id);

        if (!in_array(strtolower($item->am_approval_status), ['pending', 'reverted', 'returned'])) {
            return response()->json(['error' => 'Only pending or reverted items can be deleted.'], 422);
        }

        $parentId = $item->purchase_request_id;
        $item->delete();

        $parent = PurchaseRequest::find($parentId);
        if ($parent) {
            $parent->syncStatusFromItems('System: Item deleted');
        }

        return response()->json(['success' => 'Item deleted successfully.'], 200);
    }

    public function getPoHistory($id)
    {
        $purchaseRequestData = PurchaseRequestData::with(['purchase_order_data.purchase_order', 'purchase_order_data.supplier', 'item.unitOfMeasure', 'purchase_request'])->findOrFail($id);
        return view('management.procurement.store.purchase_request.po_history', compact('purchaseRequestData'));
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $location = CompanyLocation::find($locationId ?? $request->company_location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'PR-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseRequest::where('purchase_request_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->purchase_request_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_request_no = 'PR-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'purchase_request_no' => $purchase_request_no,
            ]);
        }

        return $purchase_request_no;
    }
}
