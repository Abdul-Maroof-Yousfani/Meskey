<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseOrderRequest;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\PaymentTerm;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseQuotation;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use App\Models\Master\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_order.index');
    }

    /**
     * Get list of categories.
     */
    // public function getList(Request $request)
    // {
    //     $PurchaseOrder = PurchaseOrderData::with('purchase_order', 'category', 'item')
    //         ->whereStatus(true)->latest()
    //         ->paginate(request('per_page', 25));

    //     return view('management.procurement.store.purchase_order.getList', compact('PurchaseOrder'));
    // }

    public function getList(Request $request)
    {
        $PurchaseOrderRaw = PurchaseOrderData::with(
            'purchase_order.purchase_quotation.purchase_request',
            'category',
            'item',
            'supplier'
        )
            ->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseOrderRaw as $row) {
            // Handle missing relationships
            $purchaseRequestNo = $row->purchase_order->purchase_quotation->purchase_request->purchase_request_no ?? 'N/A';
            $quotationNo = $row->purchase_order->purchase_quotation->purchase_quotation_no ?? 'N/A';
            $orderNo = $row->purchase_order->purchase_order_no ?? 'N/A';
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

            if ($orderNo === 'N/A') {
                continue; // Skip if no valid purchase order
            }

            // Rest of the grouping logic remains the same
            if (!isset($groupedData[$orderNo])) {
                $groupedData[$orderNo] = [
                    'request_data' => $row->purchase_order->purchase_quotation->purchase_request ?? null,
                    'quotations' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo] = [
                    'quotation_data' => $row->purchase_order->purchase_quotation ?? null,
                    'orders' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo] = [
                    'order_data' => $row->purchase_order,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$itemId])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$itemId] = [
                    'item_data' => $row,
                    'suppliers' => []
                ];
            }

            $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
        }

        // Process grouped data (unchanged)
        foreach ($groupedData as $purchaseRequestNo => $requestGroup) {
            foreach ($requestGroup['quotations'] as $quotationNo => $quotationGroup) {
                foreach ($quotationGroup['orders'] as $orderNo => $orderGroup) {
                    $requestRowspan = 0;
                    $requestItems = [];
                    $hasApprovedItem = false;

                    foreach ($orderGroup['items'] as $itemGroup) {
                        foreach ($itemGroup['suppliers'] as $supplierData) {
                            $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'} ?? 'N/A';
                            if (strtolower($approvalStatus) === 'approved') {
                                $hasApprovedItem = true;
                                break 2;
                            }
                        }
                    }

                    foreach ($orderGroup['items'] as $itemId => $itemGroup) {
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
                    $originalPurchaseRequestNo = $orderGroup['order_data']->purchase_request->purchase_request_no ?? 'N/A';

                    $processedData[] = [
                        'request_data' => $orderGroup['order_data'],
                        'request_no' => $orderNo,
                        'purchase_request_no' => $originalPurchaseRequestNo,
                        'quotation_no' => $quotationNo,
                        'created_by_id' => $orderGroup['order_data']->created_by ?? null,
                        'request_status' => $orderGroup['order_data']->am_approval_status ?? 'N/A',
                        'request_rowspan' => $requestRowspan,
                        'items' => $requestItems,
                        'has_approved_item' => $hasApprovedItem
                    ];
                    // dd($processedData);
                }
            }
        }

        // dd(array_keys($groupedData)); // Debug to check all POs

        return view('management.procurement.store.purchase_order.getList', [
            'PurchaseOrder' => $PurchaseOrderRaw,
            'GroupedPurchaseOrder' => $processedData
        ]);
    }


    public function approve_item(Request $request)
    {
        $requestId = $request->id;
        $quotationNo = $request->quotation_no;
        $supplierId = $request->supplier_id;

        $master = PurchaseRequest::with("locations")->find($requestId);
        $locations_id = $master->locations->pluck("location_id")->toArray();
       
        $quotation = null;
        $dataItems = collect();
       
        if ($quotationNo) {
            $quotation = PurchaseQuotation::where('purchase_request_id', $requestId)
                ->where('id', $quotationNo)
                ->whereIn('am_approval_status', ['approved', 'partial approved'])
                ->first();

            if ($quotation) {
                $dataItems = PurchaseQuotationData::with(['purchase_order_data', 'purchase_request', 'purchase_quotation', 'item', 'category'])
                    ->where('purchase_quotation_id', $quotation->id)
                    ->where('am_approval_status', 'approved')
                    ->get();
            }
        }

        if (!$quotation || $dataItems->isEmpty()) {
            $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category'])
                ->where('purchase_request_id', $requestId)
                ->get();

            $purchaseRequestDataIds = $dataItems->pluck('id');

            $existingQuotationCount = PurchaseOrderData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
                ->whereHas('purchase_order', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->count();
         
            if ($existingQuotationCount > 0) {
                $quotationQuantities = PurchaseOrderData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
                    ->whereHas('purchase_order', function ($q) use ($supplierId) {
                        $q->where('supplier_id', $supplierId);
                    })
                    ->select('item_id', DB::raw('SUM(qty) as total_quoted_qty'))
                    ->groupBy('item_id')
                    ->pluck('total_quoted_qty', 'item_id');

                foreach ($dataItems as $item) {
                    $quotedQty = $quotationQuantities[$item->item_id] ?? 0;
                    $remainingQty = $item->qty - $quotedQty;
                    $item->qty = max($remainingQty, 0);
                    $item->total_quoted_qty = $quotedQty;

                }
            } else {
                foreach ($dataItems as $item) {
                    $item->qty = $item->qty;
                    $item->total_quoted_qty = 0;
                }
            }

        }
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        $html = view('management.procurement.store.purchase_order.purchase_data', compact('dataItems', 'categories', 'job_orders', 'taxes'))->render();

        return response()->json([
            'html' => $html,
            'master' => $master,
            'quotation' => $quotation,
            'locations_id' => $locations_id
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::with("purchase_order")->where('am_approval_status', 'approved')->with([
            'PurchaseData' => function ($query) {
                // $query->where('am_approval_status', 'approved');
            }
        ])
            ->whereHas('PurchaseData', function ($q) {
                $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_data WHERE purchase_request_data_id = purchase_request_data.id)');
            })
            ->get();

     
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $payment_terms = PaymentTerm::select('id', 'desc')->where('status', 'active')->get();

        return view('management.procurement.store.purchase_order.create', compact('categories', 'payment_terms', 'approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseOrderRequest $request)
    {
        // dd($request->all());
        DB::beginTransaction();

        try {
            $quotation = null;
            if (!empty($request->quotation_no)) {
                $quotation = PurchaseQuotation::where('purchase_quotation_no', $request->quotation_no)->first();
            }


            $PurchaseOrder = PurchaseOrder::create([
                'purchase_order_no' => self::getNumber($request, $request->location_id, $request->purchase_date),
                'purchase_request_id' => $request->purchase_request_id,
                'purchase_quotation_id' => $request->quotation_no ?? null,
                'order_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'payment_term_id' => $request->payment_term_id ?? null,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'other_terms' => $request->other_term ?? null,
                'delivery_address' => $request->delivery_address,
                'created_by' => auth()->user()->id,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseOrderData::create([
                    'purchase_order_id' => $PurchaseOrder->id,
                    'category_id' => $request->category_id[$index],
                    'purchase_request_data_id' => $request->purchase_request_data_id[$index],
                    'purchase_quotation_data_id' => isset($request->purchase_quotation_data_id[$index]) ? $request->purchase_quotation_data_id[$index] : null,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id,
                    'tax_id' => $request->tax_id[$index] ?? null,
                    'excise_duty' => $request->excise_duty[$index] ?? 0,
                    'min_weight' => $request->min_weight[$index],
                    'color' => $request->color[$index],
                    'construction_per_square_inch' => $request->construction_per_square_inch[$index],
                    'size' => $request->size[$index],
                    'stitching' => $request->stitching[$index],
                    'micron' => $request->micron[$index],
                    'brand' => $request->brand[$index],
                    'printing_sample' => $request->printing_sample[$index],

                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if ($request->purchase_request_data_id[$index] != 0) {
                    $data = PurchaseRequestData::find($request->purchase_request_data_id[$index])->update([
                        'po_status' => 2,
                    ]);
                }

                if ($request->purchase_quotation_data_id[$index] != 0) {
                    $data = PurchaseQuotationData::find($request->purchase_quotation_data_id[$index])->update([
                        'quotation_status' => 2,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase order created successfully.',
                'data' => $PurchaseOrder,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */

    // public function edit($id)
    // {
    //     $purchaseQuotation = PurchaseQuotation::with([
    //         'quotation_data',
    //         'quotation_data.category',
    //         'quotation_data.item',
    //         'purchase_request.PurchaseData'
    //     ])->findOrFail($id);

    //     $purchaseRequest = $purchaseQuotation->purchase_request;

    //     $allowedCategoryIds = [];
    //     $allowedItemIds = [];

    //     if ($purchaseRequest && $purchaseRequest->PurchaseData) {
    //         $allowedCategoryIds = $purchaseRequest->PurchaseData->pluck('category_id')->unique()->toArray();
    //         $allowedItemIds = $purchaseRequest->PurchaseData->pluck('item_id')->unique()->toArray();
    //     }

    //     $categories = Category::select('id', 'name')
    //         ->whereIn('id', $allowedCategoryIds)
    //         ->get();

    //     $items = Product::select('id', 'name', 'category_id')
    //         ->whereIn('id', $allowedItemIds)
    //         ->get();

    //     $locations = CompanyLocation::select('id', 'name')->get();
    //     $job_orders = JobOrder::select('id', 'name')->get();

    //     $purchaseQuotationDataCount = $purchaseQuotation->quotation_data->count();

    //     return view('management.procurement.store.purchase_quotation.edit', compact(
    //         'purchaseQuotation',
    //         'categories',
    //         'items',
    //         'locations',
    //         'job_orders',
    //         'purchaseQuotationDataCount'
    //     ));
    // }


    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'purchaseOrderData',
            'purchaseOrderData.category',
            'purchaseOrderData.purchase_request_data',
            'purchaseOrderData.item',
            'purchase_request.PurchaseData',
            'purchase_quotation.quotation_data'
        ])->findOrFail($id);
        $purchaseRequest = $purchaseOrder->purchase_request;
        $purchaseQuotation = $purchaseOrder->purchase_quotation;


        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $payment_terms = PaymentTerm::select('id', 'desc')->where('status', 'active')->get();
        $taxes = Tax::select('id', 'percentage')->where('status', 'active')->get();

        // $data = PurchaseOrderData::with('purchase_order', 'category', 'item')
        //     ->findOrFail($id);
            
        $purchaseOrderDataCount = $purchaseOrder->purchaseOrderData->count();

        return view('management.procurement.store.purchase_order.edit', compact('purchaseOrder', 'categories', 'locations', 'job_orders', 'purchaseOrderDataCount', 'payment_terms', 'taxes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validated = $request->validate([
            'delivery_address' => "required",
            'purchase_date' => 'required|date',
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'location_id' => 'required|exists:company_locations,id',
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            'category_id' => 'required|array|min:1',
            'category_id.*' => 'required|exists:categories,id',

            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|exists:products,id',

            'uom' => 'nullable|array',
            'uom.*' => 'nullable|string|max:255',

            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',

            'rate' => 'required|array|min:1',
            'rate.*' => 'required|numeric|min:0.01',

            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',

            'micron' => 'nullable|array',
            'micron.*' => 'nullable|string|max:1000',
        ]);




        DB::beginTransaction();
        try {
            $PurchaseOrder = PurchaseOrder::findOrFail($id);
            $PurchaseOrder->update([
                'description' => $request->description,
                'delivery_address' => $request->delivery_address
            ]);
            PurchaseOrderData::where('purchase_order_id', $PurchaseOrder->id)->delete();

            foreach ($request->item_id as $index => $itemId) {
                PurchaseOrderData::create([
                    'purchase_order_id' => $PurchaseOrder->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id,
                    'tax_id' => $request->tax_id[$index] ?? null,
                    'excise_duty' => $request->excise_duty[$index] ?? 0,
                    'min_weight' => $request->min_weight[$index],
                    'color' => $request->color[$index],
                    'brand' => $request->brand[$index],
                    'construction_per_square_inch' => $request->construction_per_square_inch[$index],
                    'size' => $request->size[$index],
                    'stitching' => $request->stitching[$index],
                    'micron' => $request->micron[$index],
                    'printing_sample' => $request->printing_sample[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase Order updated successfully.',
                'data' => $PurchaseOrder,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to order purchase quotation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $purchaseOrder= PurchaseOrder::where("id", $id)->delete();
        $PurchaseOrderData = PurchaseOrderData::where('id', $id)->delete();
        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function manageApprovals($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $payment_terms = PaymentTerm::select('id', 'desc')->where('status', 'active')->get();
        $taxes = Tax::select('id', 'percentage')->where('status', 'active')->get();
        $data = PurchaseOrder::with(['purchaseOrderData', 'purchaseOrderData.item.unitOfMeasure'])->where('id', $id)->first();

        $purchaseOrder = PurchaseOrder::with([
            'purchaseOrderData',
            'purchaseOrderData.category',
            'purchaseOrderData.item',
            'purchaseOrderData.supplier',
            'purchase_quotation'
        ])->findOrFail($id);


        $purchaseOrderData = PurchaseOrderData::with("purchase_request_data")->where('purchase_order_id', $id)
            ->when(
                $purchaseOrder->am_approval_status === 'approved',
                function ($query) {
                    $query->where('am_approval_status', 'approved');
                }
            )
            ->get();

     
       
        return view('management.procurement.store.purchase_order.approvalCanvas', [
            'purchaseOrder' => $purchaseOrder,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'payment_terms' => $payment_terms,
            'taxes' => $taxes,
            'purchaseOrderData' => $purchaseOrderData,
            'data1' => $data,
        ]);
    }

    public function get_quotations(): array {
        $pr_id = request()->pr_id;

        $quotations = PurchaseQuotation::withSum("quotation_data as sum", "qty")
                    ->where("am_approval_status", "approved")
                    ->where("purchase_request_id", $pr_id)
                    // ->select("id", "purchase_quotation_no")
                    ->get();

        $data = [
            [
                "id" => "",
                "text" => "Select Quotation"
            ]
        ];
        foreach($quotations as $quotation) {
            $data[] = [
                "id" => $quotation->id,
                "text" => $quotation->purchase_quotation_no
            ];
        }

        return $data;
    }

    public function get_supplier(): array {
        $pq_id = request()->pq_id;
        
        $purchase_quotation = PurchaseQuotation::select("id", "supplier_id")->find($pq_id);
      

        $supplier = Supplier::select('id', 'name')->find($purchase_quotation->supplier_id);

        return [
            [
            "id" => $supplier->id,
            "text" => $supplier->name
            ]
        ];
    }

    public function get_order_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseOrder::find($requestId);

        $dataItems = PurchaseOrderData::with(['purchase_order', 'item', 'category'])
            ->where('purchase_order_id', $requestId)
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $html = view('management.procurement.store.purchase_order.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

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


    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $locationCode = $location->code ?? 'LOC';
        $prefix = 'PO-' . $date;

        // Find latest PO for the same prefix
        $latestPO = PurchaseOrder::where('purchase_order_no', 'like', "$prefix-%")
            ->orderByDesc('id')
            ->first();

        if ($latestPO) {
            // Correct field name
            $parts = explode('-', $latestPO->purchase_order_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_order_no = 'PO-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_no' => $purchase_order_no,
            ]);
        }

        return $purchase_order_no;
    }

}
