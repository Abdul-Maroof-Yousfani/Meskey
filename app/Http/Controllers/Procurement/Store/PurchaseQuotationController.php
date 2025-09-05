<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseQuotationRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseQuotation;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseQuotationController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_quotation.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseQuotationRaw = PurchaseQuotationData::with('purchase_quotation', 'category', 'item', 'approval', 'supplier')
            ->whereStatus(true)->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseQuotationRaw as $row) {
            $requestNo = $row->purchase_quotation->purchase_quotation_no;
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

            if (!isset($groupedData[$requestNo])) {
                $groupedData[$requestNo] = [
                    'request_data' => $row->purchase_quotation,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$requestNo]['items'][$itemId])) {
                $groupedData[$requestNo]['items'][$itemId] = [
                    'item_data' => $row,
                    'suppliers' => []
                ];
            }

            $groupedData[$requestNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
        }

        foreach ($groupedData as $requestNo => $requestGroup) {
            $requestRowspan = 0;
            $requestItems = [];

            $hasApprovedItem = false;
            foreach ($requestGroup['items'] as $itemGroup) {
                foreach ($itemGroup['suppliers'] as $supplierData) {
                    $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
                    if (strtolower($approvalStatus) === 'approved') {
                        $hasApprovedItem = true;
                        break 2; // Break out of both loops
                    }
                }
            }

            foreach ($requestGroup['items'] as $itemId => $itemGroup) {
                $itemRowspan = count($itemGroup['suppliers']);
                $requestRowspan += $itemRowspan;

                $itemSuppliers = [];
                $isFirstSupplier = true;

                foreach ($itemGroup['suppliers'] as $supplierKey => $supplierData) {
                    $itemSuppliers[] = [
                        'data' => $supplierData,
                        'is_first_supplier' => $isFirstSupplier,
                        'item_rowspan' => $itemRowspan
                    ];
                    $isFirstSupplier = false;
                }

                $requestItems[] = [
                    'item_data' => $itemGroup['item_data'],
                    'suppliers' => $itemSuppliers,
                    'item_rowspan' => $itemRowspan
                ];
            }

            $processedData[] = [
                'request_data' => $requestGroup['request_data'],
                'request_no' => $requestNo,
                'request_rowspan' => $requestRowspan,
                'items' => $requestItems,
                'has_approved_item' => $hasApprovedItem
            ];
        }

        return view('management.procurement.store.purchase_quotation.getList', [
            'PurchaseQuotation' => $PurchaseQuotationRaw,
            'GroupedPurchaseQuotation' => $processedData
        ]);
    }

    public function manageApprovals($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseQuotation = PurchaseQuotationData::with('purchase_quotation')
            ->findOrFail($id);

        $data = $purchaseQuotation;

        return view('management.procurement.store.purchase_quotation.approvalCanvas', compact('purchaseQuotation', 'data', 'categories', 'locations', 'job_orders'));
    }

    public function approve_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseRequest::find($requestId);
        $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category'])
            ->where('purchase_request_id', $requestId)
            ->where('am_approval_status', 'approved')
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();


        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        return response()->json(
            ['html' => $html, 'master' => $master]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::with('PurchaseData')->whereHas('PurchaseData', function ($q) {
            $q->where('am_approval_status', 'approved');
            // ->where('quotation_status', 1);
        })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();

        return view('management.procurement.store.purchase_quotation.create', compact('categories', 'approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseQuotationRequest  $request)
    {
        DB::beginTransaction();
        try {

            $datePrefix = date('m-d-Y') . '-';
            $PurchaseQuotation = PurchaseQuotation::create([
                'purchase_quotation_no' => self::getNumber($request, $request->location_id, $request->purchase_date),
                'purchase_request_id' => $request->purchase_request_id,
                'quotation_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseQuotationData::create([
                    'purchase_quotation_id' => $PurchaseQuotation->id,
                    'purchase_request_data_id' => $request->data_id[$index],
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if ($request->data_id[$index] != 0) {
                    $data =  PurchaseRequestData::find($request->data_id[$index])->update([
                        'quotation_status' => 2,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase request created successfully.',
                'data' => $PurchaseQuotation,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseQuotation = PurchaseQuotation::with('quotation_data', 'quotation_data.category', 'quotation_data.item')
            ->findOrFail($id);

        return view('management.procurement.store.purchase_quotation.edit', compact('purchaseQuotation', 'categories', 'locations', 'job_orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'purchase_date'    => 'required|date',
            'purchase_request_id'      => 'required|exists:purchase_requests,id',
            'location_id'      => 'required|exists:company_locations,id',
            'reference_no'     => 'nullable|string|max:255',
            'description'      => 'nullable|string',

            'category_id'      => 'required|array|min:1',
            'category_id.*'    => 'required|exists:categories,id',

            'item_id'          => 'required|array|min:1',
            'item_id.*'        => 'required|exists:products,id',

            'uom'              => 'nullable|array',
            'uom.*'            => 'nullable|string|max:255',

            'qty'              => 'required|array|min:1',
            'qty.*'            => 'required|numeric|min:0.01',

            'rate'              => 'required|array|min:1',
            'rate.*'            => 'required|numeric|min:0.01',

            'remarks'          => 'nullable|array',
            'remarks.*'        => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $PurchaseQuotation = PurchaseQuotation::findOrFail($id);

            PurchaseQuotationData::whereIn('id', (array) $request->data_id)->delete();

            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseQuotationData::create([
                    'purchase_quotation_id' => $PurchaseQuotation->id,
                    'purchase_request_data_id' => $request->data_id[$index],
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase Quotation updated successfully.',
                'data' => $PurchaseQuotation,
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $PurchaseOrderData = PurchaseOrderData::where('purchase_request_data_id', $id)->delete();
        $PurchaseQuotationData = PurchaseQuotationData::where('id', $id)->delete();

        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseQuotation::where('purchase_quotation_no', 'like', "$prefix-%")
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

        $purchase_quotation_no = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_quotation_no' => $purchase_quotation_no
            ]);
        }

        return $purchase_quotation_no;
    }
}
