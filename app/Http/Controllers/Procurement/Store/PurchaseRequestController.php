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
use Illuminate\Http\Request;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_request.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseRequests = PurchaseRequestData::with('purchase_request', 'category', 'item', 'approval')
            // ->when($request->filled('search'), function ($q) use ($request) {
            //     $searchTerm = '%' . $request->search . '%';
            //     return $q->where(function ($sq) use ($searchTerm) {
            //         $sq->where('purchase_request_no', 'like', $searchTerm);
            //     });
            // })
            ->whereStatus(true)->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_request.getList', compact('PurchaseRequests'));
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
        $job_orders = JobOrder::select('id', 'name')->get();
        return view('management.procurement.store.purchase_request.create', compact('categories', 'job_orders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProcurementPurchaseRequest $request)
    {
        DB::beginTransaction();

        try {
            $purchaseRequest = PurchaseRequest::create([
                'purchase_request_no' => self::getNumber($request, $request->company_location_id, $request->purchase_date),
                'purchase_date' => $request->purchase_date,
                'location_id' => $request->company_location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseRequestData::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                    foreach ($request->job_order_id[$index] as $jobOrderId) {
                        PurchaseAgainstJobOrder::create([
                            'purchase_request_id' => $purchaseRequest->id,
                            'purchase_request_data_id' => $requestData->id,
                            'job_order_id' => $jobOrderId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase request created successfully.',
                'data' => $purchaseRequest,
            ], 201);
        } 
    

        catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase request. ',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function edit($id)
    {
        $purchaseRequestData = PurchaseRequestData::findOrFail($id);
        $purchaseRequest = PurchaseRequest::with(['PurchaseData', 'PurchaseData.JobOrder', 'PurchaseData.item.unitOfMeasure'])->where('id', $purchaseRequestData->purchase_request_id)->first();
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        $locations = CompanyLocation::all();

        return view('management.procurement.store.purchase_request.edit', compact('purchaseRequest', 'purchaseRequestData', 'categories', 'job_orders', 'locations'));
    }

    public function manageApprovals($id)
    {
        $purchaseRequestData = PurchaseRequestData::findOrFail($id);
        $purchaseRequest = PurchaseRequest::with(['PurchaseData', 'PurchaseData.JobOrder', 'PurchaseData.item.unitOfMeasure'])->where('id', $purchaseRequestData->purchase_request_id)->first();
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        $locations = CompanyLocation::all();

        return view('management.procurement.store.purchase_request.approvalCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'data' => $purchaseRequestData,
            'purchaseRequestData' => $purchaseRequestData,
            'categories' => $categories,
            'job_orders' => $job_orders,
            'locations' => $locations,
        ]);
    }

    public function update(ProcurementPurchaseRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);

            $purchaseRequest->update([
                'purchase_date' => $request->purchase_date,
                'location_id' => $request->company_location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            $existingItems = $purchaseRequest->PurchaseData->pluck('id')->toArray();
            $submittedItems = [];

            foreach ($request->item_id as $index => $itemId) {
                if (!empty($request->item_row_id[$index])) {
                    $requestData = PurchaseRequestData::find($request->item_row_id[$index]);
                    if ($requestData) {
                        $requestData->update([
                            'category_id' => $request->category_id[$index],
                            'item_id' => $itemId,
                            'qty' => $request->qty[$index],
                            'remarks' => $request->remarks[$index] ?? null,
                        ]);

                        $submittedItems[] = $requestData->id;

                        PurchaseAgainstJobOrder::where('purchase_request_data_id', $requestData->id)->delete();

                        if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
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
                    $requestData = PurchaseRequestData::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'category_id' => $request->category_id[$index],
                        'item_id' => $itemId,
                        'qty' => $request->qty[$index],
                        'remarks' => $request->remarks[$index] ?? null,
                    ]);

                    $submittedItems[] = $requestData->id;

                    if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
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
            if (!empty($itemsToDelete)) {
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
        $PurchaseRequestData = PurchaseRequestData::where('id', $id)->update(['status' => 0]);
        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->company_location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseRequest::where('purchase_request_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->contract_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_request_no = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_request_no' => $purchase_request_no
            ]);
        }

        return $purchase_request_no;
    }
}
