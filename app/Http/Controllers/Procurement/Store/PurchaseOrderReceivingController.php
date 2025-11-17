<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseOrderReceivingRequest;
use App\Http\Requests\Procurement\Store\PurchaseOrderRequest;
use App\Models\Category;
use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Master\Account\Stock;
use App\Models\Master\CompanyLocation;
use App\Models\Master\GrnNumber;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReceivingController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_order_receiving.index');
    }



    public function getList(Request $request)
    {
        $PurchaseOrderRaw = PurchaseOrderReceivingData::with(
            'qc',
            'purchase_order_receiving.purchase_order.purchase_request',
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
            $purchaseRequestNo = $row->purchase_order_receiving->purchase_quotation->purchase_request->purchase_request_no ?? 'N/A';
            $quotationNo = $row->purchase_order_receiving->purchase_quotation->purchase_quotation_no ?? 'N/A';
            $orderNo = $row->purchase_order_receiving->purchase_order_receiving_no ?? 'N/A';
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

            if ($orderNo === 'N/A') {
                continue;
            }


            if (!isset($groupedData[$orderNo])) {
                $groupedData[$orderNo] = [
                    'request_data' => $row->purchase_order_receiving->purchase_quotation->purchase_request ?? null,
                    'quotations' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo] = [
                    'quotation_data' => $row->purchase_order_receiving->purchase_quotation ?? null,
                    'orders' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo] = [
                    'order_data' => $row->purchase_order_receiving,
                    'row' => $row,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo] = [
                    'qc' => $row->qc,
                    'items' => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$itemId])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$itemId] = [
                    'item_data' => $row,
                    'qc_status' => $row->qc?->is_qc_approved,
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
                                'item_rowspan' => $itemRowspan,
                            ];
                            $isFirstSupplier = false;
                        }

                        $requestItems[] = [
                            'item_data' => $itemGroup['item_data'],
                            'suppliers' => $itemSuppliers,
                            'qc_status' => $itemGroup["qc_status"],
                            'item_rowspan' => $itemRowspan
                        ];
                    }
                    $originalPurchaseRequestNo = $orderGroup['order_data']->purchase_request->purchase_request_no ?? 'N/A';
                    $originalPurchaseOrderNo = $orderGroup['order_data']->purchase_order->purchase_order_no ?? 'N/A';


                    $processedData[] = [
                        'request_data' => $orderGroup['order_data'],
                        'request_no' => $orderNo,
                        'purchase_request_no' => $originalPurchaseRequestNo,
                        'purchase_order_no' => $originalPurchaseOrderNo,
                        'quotation_no' => $quotationNo,
                        'created_by_id' => $orderGroup['order_data']->created_by ?? null,
                        'request_status' => $orderGroup['order_data']->am_approval_status ?? 'N/A',
                        'request_rowspan' => $requestRowspan,
                        'items' => $requestItems,
                        'qc_status' => $orderGroup['row']->qc?->is_qc_approved ?? null,
                        'has_approved_item' => $hasApprovedItem,
                    ];
                    // dd($processedData);
                }
            }
        }

        // dd(array_keys($groupedData)); // Debug to check all POs

        return view('management.procurement.store.purchase_order_receiving.getList', [
            'PurchaseOrderReceiving' => $PurchaseOrderRaw,
            'GroupedPurchaseOrderReceiving' => $processedData
        ]);
    }


    public function approve_item(Request $request)
    {
        $requestId = $request->id;
        $supplierId = $request->supplier_id;

        $master = PurchaseOrder::with(['supplier', 'location', 'purchase_request', 'purchaseOrderData', 'purchaseOrderData.purchase_request_data'])->find($requestId);
        // dd($master->purchaseOrderData[0]->purchase_request_data);
        $quotation = null;
        $dataItems = collect();



        if ($dataItems->isEmpty()) {
            $dataItems = PurchaseOrderData::with(['purchase_order', 'item', 'category', 'purchase_request_data'])
                ->where('purchase_order_id', $requestId)
                ->get();

            $purchaseOrderDataIds = $dataItems->pluck('id');

            $existingQuotationCount = PurchaseOrderReceivingData::whereIn('purchase_order_data_id', $purchaseOrderDataIds)
                // ->whereHas('purchase_order_receiving', function ($q) use ($supplierId) {
                //     $q->where('supplier_id', $supplierId);
                // })
                ->count();

            if ($existingQuotationCount > 0) {
                $quotationQuantities = PurchaseOrderReceivingData::whereIn('purchase_order_data_id', $purchaseOrderDataIds)
                    // ->whereHas('purchase_order_receiving', function ($q) use ($supplierId) {
                    //     $q->where('supplier_id', $supplierId);
                    // })
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
                    $item->total_quoted_qty = 0;
                    $item->qty = $item->qty;
                }
            }
        }


        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
       
        $html = view('management.procurement.store.purchase_order_receiving.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        return response()->json([
            'html' => $html,
            'master' => $master,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedPurchaseOrders = PurchaseOrder::where('am_approval_status', 'approved')->with([
            'purchaseOrderData' => function ($query) {
                // $query->where('am_approval_status', 'approved');
            }
        ])
            ->whereHas('purchaseOrderData', function ($q): void {
                $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_receiving_data WHERE purchase_order_data_id = purchase_order_data.id)');
            })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $purchaseRequests = PurchaseRequest::select('id', 'purchase_request_no')->where('am_approval_status', 'approved')->get();

        return view('management.procurement.store.purchase_order_receiving.create', compact('categories', 'approvedPurchaseOrders', 'purchaseRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseOrderReceivingRequest $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            $PurchaseOrderReceiving = PurchaseOrderReceiving::create([
                'purchase_order_receiving_no' => self::getNumber($request, $request->location_id, $request->receiving_date),
                'purchase_request_id' => $request->purchase_request_id,
                'purchase_order_id' => $request->purchase_order_id,
                'order_receiving_date' => $request->receiving_date,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
            ]);
            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseOrderReceivingData::create([
                    'purchase_order_receiving_id' => $PurchaseOrderReceiving->id,
                    'category_id' => $request->category_id[$index],
                    'purchase_order_data_id' => $request->purchase_order_data_id[$index] ?? null,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index] ?? 0,
                    'rate' => $request->rate[$index] ?? 0,
                    'total' => $request->total[$index] ?? 0,
                    'supplier_id' => $request->supplier_id,
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                // if ($request->purchase_request_data_id[$index] != 0) {
                //     $data = PurchaseRequestData::find($request->purchase_request_data_id[$index])->update([
                //         'po_status' => 2,
                //     ]);
                // }


            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase order receiving created successfully.',
                'data' => $PurchaseOrderReceiving,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order receiving.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function edit($id)
    {
        $purchaseOrderReceiving = PurchaseOrderReceiving::with([
            'purchaseOrderReceivingData',
            'purchaseOrderReceivingData.qc',
            'purchaseOrderReceivingData.category',
            'purchaseOrderReceivingData.item',
            'purchase_request.PurchaseData'
        ])->findOrFail($id);

     
        $purchaseRequest = $purchaseOrderReceiving->purchase_request;
        
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        $purchaseOrder = PurchaseOrder::select('id', 'purchase_order_no')->get();
        // $data = PurchaseOrderReceivingData::with('purchase_order_receiving', 'category', 'item')
        //     ->findOrFail($id);

        $purchaseOrderReceivingDataCount = $purchaseOrderReceiving->purchaseOrderReceivingData->count();

        return view('management.procurement.store.purchase_order_receiving.edit', compact('purchaseOrderReceiving', 'categories', 'locations', 'job_orders', 'purchaseOrderReceivingDataCount', 'purchaseOrder'));
    }

    /**
     * Update the specified resource in storage.
     */

    public function createQc(Request $request) {
        $id = $request->id;
        $accepted_quantity = $request->accepted_qty;
        $rej_qty = $request->rej_qty;
        $deduction_per_bag = $request->deduction_per_bag;

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::find($id);


        try {
            if($purchaseOrderReceivingData->qc()?->exists()) {
                $purchaseOrderReceivingData->qc()->update([
                    "accepted_quantity" => $accepted_quantity,
                    "rejected_quantity" => $rej_qty,
                    "deduction_per_bag" => $deduction_per_bag
                ]);
            } else {
                $purchaseOrderReceivingData->qc()->create([
                    "accepted_quantity" => $accepted_quantity,
                    "rejected_quantity" => $rej_qty,
                    "deduction_per_bag" => $deduction_per_bag
                ]);
            }
         
            return response()->json([
                'success' => 'QC has been created successfully.',
                'data' => $purchaseOrderReceivingData,
            ], 200);
        } catch(\Exception $e) {
            return response()->json([
                'success' => 'Error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validated = $request->validate([
            'receiving_date' => 'required|date',
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

            // 'rate' => 'required|array|min:1',
            // 'rate.*' => 'required|numeric|min:0.01',

            'remarks' => 'nullable|array',
            'remarks.*' => 'nullable|string|max:1000',
        ]);




        DB::beginTransaction();
        try {
            $PurchaseOrderReceiving = PurchaseOrderReceiving::findOrFail($id);
            $PurchaseOrderReceiving->update([
                "description" => $request->description
            ]);

            PurchaseOrderReceivingData::where('purchase_order_receiving_id', $PurchaseOrderReceiving->id)->delete();


            foreach ($request->item_id as $index => $itemId) {
                PurchaseOrderReceivingData::create([
                    'purchase_order_receiving_id' => $PurchaseOrderReceiving->id,
                    'category_id' => $request->category_id[$index],
                    'purchase_order_data_id' => $request->purchase_order_data_id[$index] ?? null,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index] ?? 0,
                    'rate' => $request->rate[$index] ?? 0,
                    'total' => $request->total[$index] ?? 0,
                    'supplier_id' => $request->supplier_id,
                    "accepted_qty" => $request->accepted_qty[$index] ?? 0,
                    "rejected_qty" => $request->rejected_qty[$index] ?? 0,
                    'remarks' => $request->remarks[$index] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase Order receiving updated successfully.',
                'data' => $PurchaseOrderReceiving,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to order purchase order receiving.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $PurchaseOrderReceiving = PurchaseOrderReceiving::where('id', $id)->delete();
        $PurchaseOrderReceivingData = PurchaseOrderReceivingData::where('purchase_order_receiving_id', $id)->delete();
        return response()->json(['success' => 'Purchase Order Receiving deleted successfully.'], 200);
    }

    public function manageApprovals($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        // $job_orders = JobOrder::get();
        // dd($job_orders);
        $purchaseOrderReceiving = PurchaseOrderReceiving::with([
            'purchaseOrderReceivingData',
            'purchaseOrderReceivingData.category',
            'purchaseOrderReceivingData.item',
            'purchaseOrderReceivingData.supplier'
        ])->findOrFail($id);


        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("purchase_order_data", "purchase_order_data.purchase_request_data")->where('purchase_order_receiving_id', $id)
            ->when(
                $purchaseOrderReceiving->am_approval_status === 'approved',
                function ($query) {
                    $query->where('am_approval_status', 'approved');
                }
            )
            ->get();
        
        $purchaseOrder = PurchaseOrder::select('id', 'purchase_order_no')->get();

        return view('management.procurement.store.purchase_order_receiving.approvalCanvas', [
            'purchaseOrderReceiving' => $purchaseOrderReceiving,
            'categories' => $categories,
            'locations' => $locations,
            // 'job_orders' => $job_orders,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderReceivingData' => $purchaseOrderReceivingData,
            'data1' => $purchaseOrderReceiving,
        ]);
    }

    public function get_order_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseOrderReceiving::find($requestId);

        $dataItems = PurchaseOrderReceivingData::with(['purchase_order_receiving', 'item', 'category'])
            ->where('purchase_order_receiving_id', $requestId)
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $html = view('management.procurement.store.purchase_order_receiving.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

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
        $prefix = 'GRN-' . $locationCode . '-' . $date;

        // Find latest PO for the same prefix
        $latestPO = PurchaseOrderReceiving::where('purchase_order_receiving_no', 'like', "$prefix-%")
            ->orderByDesc('id')
            ->first();

        if ($latestPO) {
            // Correct field name
            $parts = explode('-', $latestPO->purchase_order_receiving_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_order_receiving_no = 'GRN-' . $locationCode . '-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_receiving_no' => $purchase_order_receiving_no,
            ]);
        }

        return $purchase_order_receiving_no;
    }
}
