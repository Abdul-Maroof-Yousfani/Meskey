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
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_request.index');
    }

    public function getItems()
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::with('packing_items')->whereIn('id', json_decode(request()->job_orders))->get();

        return view('management.procurement.store.purchase_request.getItem', compact('job_orders', 'categories'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseRequests = PurchaseRequestData::with('purchase_request', 'category', 'item', 'approval')
            ->whereStatus(true)
            ->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];
        foreach ($PurchaseRequests as $row) {
            $requestNo = $row->purchase_request?->purchase_request_no;
            $created_by_id = $row->purchase_request?->created_by;
            $itemId = $row->item->id ?? 'unknown';

            if (! isset($groupedData[$requestNo])) {
                $groupedData[$requestNo] = [
                    'request_data' => $row->purchase_request,
                    'items' => [],
                ];
            }

            $groupedData[$requestNo]['items'][$itemId] = [
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

            foreach ($requestGroup['items'] as $itemId => $itemGroup) {
                $requestItems[] = [
                    'item_data' => $itemGroup['item_data'],
                    'item_rowspan' => 1,
                ];
            }

            $requestRowspan = count($requestItems);

            $processedData[] = [
                'request_data' => $requestGroup['request_data'],
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        return view('management.procurement.store.purchase_request.create', compact('categories', 'job_orders'));
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
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
                'job_orders' => collect($request->job_order_id)->flatten()->filter()
            ]);

            foreach ($company_locations as $company_location) {
                $purchaseRequest->locations()->create([
                    'location_id' => $company_location,
                ]);
            }

            $purchase_request_data_ids = [];
            foreach ($request->item_id as $index => $itemId) {
                $printingSamplePath = null;

                if ($request->hasFile('printing_sample.'.$index)) {
                    $file = $request->file('printing_sample.'.$index);
                    $printingSamplePath = $file->store('printing_samples', 'public');
                }

                $requestData = PurchaseRequestData::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'approved_qty' => 0,
                    'min_weight' => $request->min_weight[$index] ?? null,
                    'color' => $request->color[$index] ?? null,
                    'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                    'size' => $request->size[$index] ?? null,
                    'stitching' => $request->stitching[$index] ?? null,
                    'micron' => $request->micron[$index] ?? null,
                    'printing_sample' => $printingSamplePath,
                    'brand_id' => $request->brands[$index],
                    'remarks' => $request->remarks[$index] ?? null,

                ]);

                // Ensure $request->job_order_id exists and is an array
                if (isset($request->job_order_id) && is_array($request->job_order_id)) {
                    foreach ($request->job_order_id as $job_order_ids) {
                        // Ensure nested item is also an array
                        if (is_array($job_order_ids)) {
                            foreach ($job_order_ids as $job_order_id) {
                                // Skip if job_order_id is null or empty
                                if (empty($job_order_id)) {
                                    continue;
                                }

                                try {
                                    // Safely create record
                                    PurchaseAgainstJobOrder::create([
                                        'purchase_request_id' => $purchaseRequest->id,
                                        'purchase_request_data_id' => $requestData->id,
                                        'job_order_id' => $job_order_id,
                                    ]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    // Log the error instead of breaking
                                    \Log::error('Failed to insert PurchaseAgainstJobOrder: '.$e->getMessage());

                                    continue; // skip this item and continue loop
                                }
                            }
                        }
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
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $locations = CompanyLocation::all();

        return view('management.procurement.store.purchase_request.edit', compact('locations_id', 'location_names', 'purchaseRequest', 'purchaseRequestData', 'categories', 'job_orders', 'locations'));
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
        $locations = CompanyLocation::all();

        // dd($purchaseRequest);
        return view('management.procurement.store.purchase_request.approvalCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'data' => $purchaseRequest,
            'purchaseRequestData' => $purchaseRequestData,
            'categories' => $categories,
            'job_orders' => $job_orders,
            'locations' => $locations,
            'locations_id' => $locations_id,
            'location_names' => $location_names,

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
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'am_change_made' => 1,
            ];
            // echo $purchaseRequest->am_approval_status;

            if ($purchaseRequest->am_approval_status == 'reverted') {
                $updateData['am_approval_status'] = 'pending';
            }

            $purchaseRequest->update($updateData);

            $existingItems = $purchaseRequest->PurchaseData->pluck('id')->toArray();
            $submittedItems = [];

            foreach ($request->item_id as $index => $itemId) {
                if (! empty($request->item_row_id[$index])) {
                    $requestData = PurchaseRequestData::find($request->item_row_id[$index]);
                    $printingSamplePath = $requestData->printing_sample;

                    if ($requestData) {
                        if ($request->hasFile('printing_sample.'.$index)) {
                            $file = $request->file('printing_sample.'.$index);
                            $printingSamplePath = $file->store('printing_samples', 'public');
                        }

                        $requestData->update([
                            'category_id' => $request->category_id[$index],
                            'item_id' => $itemId,
                            'qty' => $request->qty[$index],
                            'min_weight' => $request->min_weight[$index] ?? null,
                            'color' => $request->color[$index] ?? null,
                            'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                            'size' => $request->size[$index] ?? null,
                            'stitching' => $request->stitching[$index] ?? null,
                            'printing_sample' => $printingSamplePath,
                            'remarks' => $request->remarks[$index] ?? null,
                            'brand_id' => $request->brands[$index],
                            'micron' => $request->micron[$index],
                        ]);
                        $submittedItems[] = $requestData->id;

                        PurchaseAgainstJobOrder::where('purchase_request_data_id', $requestData->id)->delete();

                        if (! empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                            foreach ($request->job_order_id[$index] as $jobOrderId) {
                                PurchaseAgainstJobOrder::create([
                                    'purchase_request_id' => $purchaseRequest->id,
                                    'purchase_request_data_id' => $requestData->id,
                                    'job_order_id' => $jobOrderId,
                                ]);
                            }
                        }
                    }
                } else {

                    if ($request->hasFile('printing_sample.'.$index)) {
                        $file = $request->file('printing_sample.'.$index);
                        $printingSamplePath = $file->store('printing_samples', 'public');
                    }

                    $requestData = PurchaseRequestData::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'category_id' => $request->category_id[$index],
                        'item_id' => $itemId,
                        'qty' => $request->qty[$index],
                        'approved_qty' => 0,
                        'min_weight' => $request->min_weight[$index] ?? null,
                        'color' => $request->color[$index] ?? null,
                        'construction_per_square_inch' => $request->construction_per_square_inch[$index] ?? null,
                        'size' => $request->size[$index] ?? null,
                        'stitching' => $request->stitching[$index] ?? null,
                        'printing_sample' => $printingSamplePath,
                        'brand_id' => $request->brands[$index],
                        'remarks' => $request->remarks[$index] ?? null,
                        'micron' => $request->micron[$index],
                    ]);

                    $submittedItems[] = $requestData->id;

                    if (! empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                        foreach ($request->job_order_id[$index] as $jobOrderId) {
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
                PurchaseRequestData::whereIn('id', $itemsToDelete)->delete();
                PurchaseAgainstJobOrder::whereIn('purchase_request_data_id', $itemsToDelete)->delete();
            }

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
        $PurchaseQuotationData = PurchaseQuotationData::where('purchase_request_data_id', $id)->delete();
        $PurchaseOrderData = PurchaseOrderData::where('purchase_request_data_id', $id)->delete();
        $PurchaseRequestData = PurchaseRequestData::where('id', $id)->update(['status' => '0']);

        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
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
