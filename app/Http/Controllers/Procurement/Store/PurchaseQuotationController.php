<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseQuotationRequest;
use App\Models\Product;
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
// use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;
// use Illuminate\Validation\ValidationException;
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
        $PurchaseQuotationRaw = PurchaseQuotationData::with(
            'purchase_quotation.purchase_request',
            'category',
            'item',
            'supplier'
        )
            ->whereStatus(true)
            ->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseQuotationRaw as $row) {
            if (!$row->purchase_quotation || !$row->purchase_quotation->purchase_request) {
                continue;
            }

            $purchaseRequestNo = $row->purchase_quotation->purchase_request->purchase_request_no;
            $requestNo = $row->purchase_quotation->purchase_quotation_no; // purchase quotation no
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

            // Group by purchase_request_no → purchase_quotation_no → item_id → suppliers
            if (!isset($groupedData[$purchaseRequestNo])) {
                $groupedData[$purchaseRequestNo] = [
                    'request_data' => $row->purchase_quotation->purchase_request,
                    'quotations' => []
                ];
            }

            if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo])) {
                $groupedData[$purchaseRequestNo]['quotations'][$requestNo] = [
                    'quotation_data' => $row->purchase_quotation,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId])) {
                $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId] = [
                    'item_data' => $row,
                    'suppliers' => []
                ];
            }

            $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
        }

        // Build $processedData while preserving your structure
        foreach ($groupedData as $purchaseRequestNo => $requestGroup) {
            foreach ($requestGroup['quotations'] as $quotationNo => $quotationGroup) {
                $requestRowspan = 0;
                $requestItems = [];
                $hasApprovedItem = false;

                foreach ($quotationGroup['items'] as $itemGroup) {
                    foreach ($itemGroup['suppliers'] as $supplierData) {
                        $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
                        if (strtolower($approvalStatus) === 'approved') {
                            $hasApprovedItem = true;
                            break 2;
                        }
                    }
                }

                foreach ($quotationGroup['items'] as $itemId => $itemGroup) {
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
                    'request_data' => $quotationGroup['quotation_data'],
                    'request_no' => $quotationNo,
                    'purchase_request_no' => $purchaseRequestNo,
                    'created_by_id' => $quotationGroup['quotation_data']->created_by,
                    'request_status' => $quotationGroup['quotation_data']->am_approval_status,
                    'request_rowspan' => $requestRowspan,
                    'items' => $requestItems,
                    'has_approved_item' => $hasApprovedItem
                ];
            }
        }

        return view('management.procurement.store.purchase_quotation.getList', [
            'PurchaseQuotation' => $PurchaseQuotationRaw,
            'GroupedPurchaseQuotation' => $processedData
        ]);
    }

    public function comparison_list()
    {
        return view('management.procurement.store.purchase_quotation.comparisonList');
    }

    public function get_comparison(Request $request)
    {
        $PurchaseQuotationRaw = PurchaseQuotationData::with(
            'purchase_quotation.purchase_request',
            'category',
            'item',
            'supplier'
        )
            ->whereStatus(true)
            ->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseQuotationRaw as $row) {
            if (!$row->purchase_quotation || !$row->purchase_quotation->purchase_request) {
                continue;
            }

            
            $purchaseRequestNo = $row->purchase_quotation->purchase_request->purchase_request_no;
            $groupedData[$purchaseRequestNo]["canApprove"] = $row->canApprove();
            $requestNo = $row->purchase_quotation->purchase_quotation_no; // purchase quotation no
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

            // Group by purchase_request_no → purchase_quotation_no → item_id → suppliers
            if (!isset($groupedData[$purchaseRequestNo])) {
                $groupedData[$purchaseRequestNo] = [
                    'request_data' => $row->purchase_quotation->purchase_request,
                    'quotations' => []
                ];
            }

            if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo])) {
                $groupedData[$purchaseRequestNo]['quotations'][$requestNo] = [
                    'quotation_data' => $row->purchase_quotation,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId])) {
                $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId] = [
                    'item_data' => $row,
                    'suppliers' => []
                ];
            }

            $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
        }

        foreach ($groupedData as $purchaseRequestNo => $requestGroup) {
            foreach ($requestGroup['quotations'] as $quotationNo => $quotationGroup) {
                $requestRowspan = 0;
                $quotaionRowspan = 0;
                $requestItems = [];
                $hasApprovedItem = false;

                foreach ($quotationGroup['items'] as $itemGroup) {
                    foreach ($itemGroup['suppliers'] as $supplierData) {
                        $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
                        if (strtolower($approvalStatus) === 'approved') {
                            $hasApprovedItem = true;
                            break 2;
                        }
                    }
                }

                foreach ($quotationGroup['items'] as $itemId => $itemGroup) {
                    $itemRowspan = count($itemGroup['suppliers']);
                    $quotaionCount = count($requestGroup['quotations']);
                    $requestRowspan += $itemRowspan;
                    $quotaionRowspan += $quotaionCount;

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
                    'request_data' => $quotationGroup['quotation_data'],
                    'request_no' => $quotationNo,
                    'purchase_request_no' => $purchaseRequestNo,
                    'created_by_id' => $quotationGroup['quotation_data']->created_by,
                    'request_status' => $quotationGroup['quotation_data']->am_approval_status,
                    'request_rowspan' => $requestRowspan,
                    'quotaion_rowspan' => $quotaionRowspan,


                    'items' => $requestItems,
                    'has_approved_item' => $hasApprovedItem
                ];

            }
        }

        return view('management.procurement.store.purchase_quotation.getComparison', [
            'PurchaseQuotation' => $PurchaseQuotationRaw,
            'GroupedPurchaseQuotation' => $processedData,
            "groupedData" => $groupedData
        ]);

        // dd("ok");
        // $PurchaseRequests = PurchaseRequestData::with('purchase_request', 'category', 'item', 'approval', 'purchase_quotation_data.purchase_quotation')
        //     ->whereStatus(true)
        //     ->latest()
        //     ->paginate(request('per_page', 25));

        // $groupedData = [];
        // $processedData = [];
        // foreach ($PurchaseRequests as $row) {
        //     $requestNo = $row->purchase_request->purchase_request_no;
        //     $created_by_id = $row->purchase_request->created_by;
        //     $itemId = $row->item->id ?? 'unknown';

        //     if (!isset($groupedData[$requestNo])) {
        //         $groupedData[$requestNo] = [
        //             'request_data' => $row->purchase_request,
        //             'items' => []
        //         ];
        //     }

        //     $groupedData[$requestNo]['items'][$itemId] = [
        //         'item_data' => $row,
        //     ];
        // }

        // foreach ($groupedData as $requestNo => $requestGroup) {
        //     $requestItems = [];
        //     $hasApprovedItem = false;

        //     foreach ($requestGroup['items'] as $itemGroup) {
        //         $approvalStatus = $itemGroup['item_data']
        //             ?->{$itemGroup['item_data']->getApprovalModule()->approval_column ?? 'am_approval_status'};
        //         if (strtolower($approvalStatus) === 'approved') {
        //             $hasApprovedItem = true;
        //             break;
        //         }
        //     }

        //     foreach ($requestGroup['items'] as $itemId => $itemGroup) {
        //         $requestItems[] = [
        //             'item_data' => $itemGroup['item_data'],
        //             'item_rowspan' => 1
        //         ];
        //     }

        //     $requestRowspan = count($requestItems);

        //     $processedData[] = [
        //         'request_data' => $requestGroup['request_data'],
        //         'request_no' => $requestNo,
        //         'created_by_id' => $requestGroup['request_data']->created_by,
        //         'request_status' => $requestGroup['request_data']->am_approval_status,
        //         'request_rowspan' => $requestRowspan,
        //         'items' => $requestItems,
        //         'has_approved_item' => $hasApprovedItem
        //     ];
        // }
        // return view('management.procurement.store.purchase_quotation.getComparison', [
        //     'PurchaseRequests' => $PurchaseRequests,
        //     'GroupedPurchaseRequests' => $processedData
        // ]);


    }

    public function manageComparisonApprovals($purchase_request_id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseRequest = PurchaseRequest::with([
            'PurchaseData',
            'PurchaseData.category',
            'PurchaseData.item',
        ])->findOrFail($purchase_request_id);

        // dd(count($purchaseRequest->PurchaseData));
        

        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->where('am_approval_status', 'pending')->pluck('id');

        $PurchaseQuotationIds2 = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $PurchaseQuotationData = PurchaseQuotationData::with(['purchase_request', 'purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
            ->where('am_approval_status', 'pending')
            //     ->whereHas('purchase_quotation', function ($query) {
            //     $query->whereNotIn('am_approval_status', ['partial_approved']);
            // })
            ->get();

        $data = PurchaseQuotationData::with(relations: ['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds2)
            // ->where('am_approval_status', 'pending')
            ->latest()->first();
      
        return view('management.procurement.store.purchase_quotation.approvalComparisonCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'PurchaseQuotationData' => $PurchaseQuotationData,
            'data1' => $data,
        ]);
    }

    public function manageComparisonApprovalsView($purchase_request_id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseRequest = PurchaseRequest::with([
            'PurchaseData',
            'PurchaseData.category',
            'PurchaseData.item',
        ])->findOrFail($purchase_request_id);


        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $PurchaseQuotationData = PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
            ->where('am_approval_status', operator: 'approved')
            ->get();

        $data = PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
            ->where('am_approval_status', 'approved')
            ->first();

            if($data == null){
                $data = PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
            ->first();
            }

        return view('management.procurement.store.purchase_quotation.approvalComparisonCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'PurchaseQuotationData' => $PurchaseQuotationData,
            'data1' => $data,
        ]);
    }



    public function manageApprovals($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseQuotation = PurchaseQuotation::with([
            'quotation_data',
            'quotation_data.category',
            'quotation_data.item',
            'quotation_data.supplier'
        ])->findOrFail($id);


        $purchaseQuotationData = PurchaseQuotationData::where('purchase_quotation_id', $id)
            // ->when(
            //     $purchaseQuotation->am_approval_status === 'approved',
            //     function ($query) {
            //         $query->where('am_approval_status', 'approved');
            //     }
            // )
            ->when(
                in_array($purchaseQuotation->am_approval_status, ['approved', 'partial approved']),
                function ($query) {
                    $query->where('am_approval_status', 'approved');
                }
            )

            ->get();

        return view('management.procurement.store.purchase_quotation.approvalCanvas', [
            'purchaseQuotation' => $purchaseQuotation,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'purchaseQuotationData' => $purchaseQuotationData,
            'data1' => $purchaseQuotation,
        ]);
    }





    // public function approve_item(Request $request)
    // {
    //     $requestId = $request->id;

    //     $master = PurchaseRequest::find($requestId);
    //     $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category'])
    //         ->where('purchase_request_id', $requestId)
    //         // ->where('am_approval_status', 'approved')
    //         ->get();

    //     $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
    //     $job_orders = JobOrder::select('id', 'name')->get();


    //     $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

    //     return response()->json(
    //         ['html' => $html, 'master' => $master]
    //     );
    // }

    public function approve_item(Request $request)
    {
        $requestId = $request->id;
        $supplierId = $request->supplier_id;

        $master = PurchaseRequest::find($requestId);

        $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category'])
            ->where('purchase_request_id', $requestId)
            ->get();

        $purchaseRequestDataIds = $dataItems->pluck('id');

        $existingQuotationCount = PurchaseQuotationData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
            ->whereHas('purchase_quotation', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->count();
        $quantities = [];
        if ($existingQuotationCount > 0) {
            $quotationQuantities = PurchaseQuotationData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
                ->whereHas('purchase_quotation', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->select('item_id', DB::raw('SUM(qty) as total_quoted_qty'))
                ->groupBy('item_id')
                ->pluck('total_quoted_qty', 'item_id');

            foreach ($dataItems as $item) {
                $quotedQty = $quotationQuantities[$item->item_id] ?? 0;
                $remainingQty = $item->qty - $quotedQty;
                $item->qty = max($remainingQty, 0);
            }
        } else {
            foreach ($dataItems as $item) {
                if($item->qty) {
                    $quantities[] = $item->qty;
                } else {
                    $quantities[] = 0;
                }
                $item->qty = $item->qty;
            }
        }

        $purchaseRequestDataCount = $dataItems->count();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        
        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        $categoryIds = $dataItems->pluck('category_id')->unique()->values();
        $itemIds = $dataItems->pluck('item_id')->unique()->values();

        return response()->json([
            'html' => $html,
            'master' => $master,
            'allowed_categories' => $categoryIds,
            'allowed_items' => $itemIds,
            'purchaseRequestDataCount' => $purchaseRequestDataCount,
            "quantities" => $quantities
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::with('PurchaseData')->where('am_approval_status', 'approved')->whereHas('PurchaseData', function ($q) {
            // $q->where('am_approval_status', 'approved');
            // ->where('quotation_status', 1);
                $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_data WHERE purchase_request_data_id = purchase_request_data.id)');
            
        })
            ->get();
        

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();

        return view('management.procurement.store.purchase_quotation.create', compact('categories', 'approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseQuotationRequest $request)
    {
        DB::beginTransaction();
        try {

            $datePrefix = date('m-d-Y') . '-';
            $PurchaseQuotation = PurchaseQuotation::create([
                'purchase_quotation_no' => self::getNumber($request, $request->location_id, $request->purchase_date),
                'purchase_request_id' => $request->purchase_request_id,
                'quotation_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
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
                    'supplier_id' => $request->supplier_id,
                    'remarks' => $request->remarks[$index] ?? null,
                ]);


                if ($request->data_id[$index] != 0) {
                    $data = PurchaseRequestData::find($request->data_id[$index])->update([
                        'quotation_status' => 2,
                    ]);
                }
            }
       
            DB::commit();

            return response()->json([
                'success' => 'Purchase quotation created successfully.',
                'data' => $PurchaseQuotation,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase quotation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $purchaseQuotation = PurchaseQuotation::with([
            'quotation_data',
            'quotation_data.category',
            'quotation_data.item',
            'purchase_request.PurchaseData'
        ])->findOrFail($id);
        

        $purchaseRequest = $purchaseQuotation->purchase_request;

        $allowedCategoryIds = [];
        $allowedItemIds = [];

        if ($purchaseRequest && $purchaseRequest->PurchaseData) {
            $allowedCategoryIds = $purchaseRequest->PurchaseData->pluck('category_id')->unique()->toArray();
            $allowedItemIds = $purchaseRequest->PurchaseData->pluck('item_id')->unique()->toArray();
        }

        $categories = Category::select('id', 'name')
            ->whereIn('id', $allowedCategoryIds)
            ->get();

        $items = Product::select('id', 'name', 'category_id')
            ->whereIn('id', $allowedItemIds)
            ->get();

        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseQuotationDataCount = $purchaseQuotation->quotation_data->count();

        return view('management.procurement.store.purchase_quotation.edit', compact(
            'purchaseQuotation',
            'categories',
            'items',
            'locations',
            'job_orders',
            'purchaseQuotationDataCount'
        ));
    }

    // public function edit($id)
    // {
    //     $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
    //     $locations = CompanyLocation::select('id', 'name')->get();
    //     $job_orders = JobOrder::select('id', 'name')->get();

    //     $purchaseQuotation = PurchaseQuotation::with('quotation_data', 'quotation_data.category', 'quotation_data.item')
    //         ->findOrFail($id);

    //     return view('management.procurement.store.purchase_quotation.edit', compact('purchaseQuotation', 'categories', 'locations', 'job_orders'));
    // }
    public function get_quotation_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseQuotation::find($requestId);

        $dataItems = PurchaseQuotationData::with(['purchase_quotation', 'item', 'category'])
            ->where('purchase_quotation_id', $requestId)
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        // Extract IDs for frontend restriction logic
        $categoryIds = $dataItems->pluck('category_id')->unique()->values();
        $itemIds = $dataItems->pluck('item_id')->unique()->values();

        return response()->json([
            'html' => $html,
            'master' => $master,
            'allowed_categories' => $categoryIds,
            'allowed_items' => $itemIds,
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         // 'purchase_date' => 'required|date',
    //         'purchase_request_id' => 'required|exists:purchase_requests,id',
    //         'location_id' => 'required|exists:company_locations,id',
    //         'reference_no' => 'nullable|string|max:255',
    //         'description' => 'nullable|string',

    //         'category_id' => 'required|array|min:1',
    //         'category_id.*' => 'required|exists:categories,id',

    //         'item_id' => 'required|array|min:1',
    //         'item_id.*' => 'required|exists:products,id',

    //         'uom' => 'nullable|array',
    //         'uom.*' => 'nullable|string|max:255',

    //         // 'qty' => 'required|array|min:1',
    //         // 'qty.*' => 'required|numeric|min:0.01',

    //         'rate' => 'required|array|min:1',
    //         'rate.*' => 'required|numeric|min:0.01',

    //         'remarks' => 'nullable|array',
    //         'remarks.*' => 'nullable|string|max:1000',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $PurchaseQuotation = PurchaseQuotation::findOrFail($id);

    //         PurchaseQuotationData::whereIn('id', (array) $request->data_id)->delete();

    //         foreach ($request->item_id as $index => $itemId) {
    //             $requestData = PurchaseQuotationData::create([
    //                 'purchase_quotation_id' => $PurchaseQuotation->id,
    //                 'purchase_request_data_id' => $request->data_id[$index],
    //                 'category_id' => $request->category_id[$index],
    //                 'item_id' => $itemId,
    //                 'qty' => $request->qty[$index],
    //                 'rate' => 0,
    //                 'total' => 0,
    //                 'supplier_id' => $request->supplier_id[$index],
    //                 'remarks' => $request->remarks[$index] ?? null,
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => 'Purchase Quotation updated successfully.',
    //             'data' => $PurchaseQuotation,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollback();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update purchase quotation.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }



    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'location_id' => 'required|exists:company_locations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            'category_id' => 'required|array|min:1',
            'category_id.*' => 'required|exists:categories,id',

            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',

            // 'supplier_id' => 'required|array|min:1',
            // 'supplier_id.*' => 'required|exists:suppliers,id',

            'uom' => 'nullable|array',
            'uom.*' => 'nullable|string|max:255',

            'rate' => 'required|array|min:1',
            'rate.*' => 'required|numeric|min:0.01',

            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',
        ]);

        // $validator->after(function ($validator) use ($request) {
        //     $itemSupplierPairs = [];

        //     foreach ($request->item_id as $index => $itemId) {
        //         $supplierId = $request->supplier_id ?? null;

        //         if (!$supplierId)
        //             continue;

        //         $pairKey = $itemId . '_' . $supplierId;

        //         if (isset($itemSupplierPairs[$pairKey])) {
        //             $validator->errors()->add(
        //                 "supplier_id.$index",
        //                 "The supplier and item combination already exists in this quotation (Item ID: $itemId, Supplier ID: $supplierId)."
        //             );
        //         }

        //         $itemSupplierPairs[$pairKey] = true;
        //     }
        // });

        // $validated = $validator->validate();

        // Continue your update logic after successful validation
        DB::beginTransaction();
        try {
            $PurchaseQuotation = PurchaseQuotation::findOrFail($id);

            PurchaseQuotationData::where('purchase_quotation_id', $PurchaseQuotation->id)->delete();

            foreach ($request->item_id as $index => $itemId) {
                PurchaseQuotationData::create([
                    'purchase_quotation_id' => $PurchaseQuotation->id,
                    'purchase_request_data_id' => $request->data_id[$index],
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index] ?? 0,
                    'rate' => $request->rate[$index],
                    'total' => ($request->qty[$index] ?? 0) * $request->rate[$index],
                    'supplier_id' => $request->supplier_id,
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
                'message' => 'Failed to update purchase quotation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $PurchaseOrderData = PurchaseQuotation::where('id', $id)->delete();
        $PurchaseQuotationData = PurchaseQuotationData::where('purchase_quotation_id', $id)->delete();

        return response()->json(['success' => 'Purchase quotation deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'PQ-' . $location->code . '-' . $date;


        // Find latest quotation with the same prefix
        $latestContract = PurchaseQuotation::where('purchase_quotation_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = $date;

        if ($latestContract) {
            // FIX: use purchase_quotation_no instead of contract_no
            $parts = explode('-', $latestContract->purchase_quotation_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_quotation_no = 'PQ-' . $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_quotation_no' => $purchase_quotation_no
            ]);
        }

        return $purchase_quotation_no;
    }

}
