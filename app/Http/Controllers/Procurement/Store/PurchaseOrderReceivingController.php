<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseOrderReceivingRequest;
use App\Http\Requests\Procurement\Store\PurchaseOrderRequest;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalModuleRole;
use App\Models\Category;
use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Master\Account\Stock;
use App\Models\Master\CompanyLocation;
use App\Models\Master\GrnNumber;
use App\Models\Procurement\Store\PurchaseBagQC;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Product;
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
            'purchase_order_data',
            'purchase_order_receiving.purchase_order.purchase_request',
            'category',
            'item',
            'supplier'
        )
            ->orderBy("purchase_order_receiving_id", "desc")
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseOrderRaw as $row) {
            $dc_no = $row->purchase_order_receiving->dc_no;
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
                    "item_qc" => $row->qc,
                    'qc_status' => $row->qc?->am_approval_status,
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

                    $approvalModule = ApprovalModule::select("id")->where("slug", "qc")->first();
                    $hasApprovalPermission = ApprovalModuleRole::where("module_id", $approvalModule->id)
                                                                ->where("role_id", auth()->user()->id)
                                                                ->exists();

                    

                    $processedData[] = [
                        'request_data' => $orderGroup['order_data'],
                        'request_no' => $orderNo,
                        'purchase_request_no' => $originalPurchaseRequestNo,
                        'purchase_order_no' => $originalPurchaseOrderNo,
                        'quotation_no' => $quotationNo,
                        'canApprove' => $hasApprovalPermission,
                        'created_by_id' => $orderGroup['order_data']->created_by ?? null,
                        'request_status' => $orderGroup['order_data']->am_approval_status ?? 'N/A',
                        'request_rowspan' => $requestRowspan,
                        'items' => $requestItems,
                        'dc_no' => $dc_no,
                        'qc_status' => $orderGroup['row']->qc?->am_approval_status ?? null,
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
        $locations_id = $master?->purchase_request?->locations?->pluck("location_id")->toArray();
        $location_ids = $master?->purchase_request?->locations?->pluck("location_id")->toArray();
        $company_locations = CompanyLocation::select("id", "name")->whereIn("id", $location_ids)->get();
        $location_dropdowns = [];

        foreach($company_locations as $company_location) {
            $location_dropdowns[] = [
                "id" => $company_location->id,
                "text" => $company_location->name
            ];
        }


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
        // $job_orders = JobOrder::select('id', 'name')->get();
       
        $html = view('management.procurement.store.purchase_order_receiving.purchase_data', compact('dataItems', 'categories', 'location_dropdowns'))->render();

        return response()->json([
            'html' => $html,
            'master' => $master,
            "locations_id" => $locations_id,
            "location_dropdowns" => $location_dropdowns
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
            
            // ->whereHas('purchaseOrderData', function ($q): void {
            //     $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_receiving_data WHERE purchase_order_data_id = purchase_order_data.id)');
            // })
            ->get()
            ->reject(function($purchaseOrder) {
                $purchase_order_datas = $purchaseOrder->purchaseOrderData;

                foreach($purchase_order_datas as $purchase_order_data) {
                    $po_id = $purchase_order_data->id;
                    $overall_qty = $purchase_order_data->qty;
                    if(!$po_id) return false;
                    $stock = getStockByGrnDataId($po_id);
                    
                   
                    if($stock < $overall_qty) return false;
                }
                return true;
            });

  

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

             
            $grn = self::getNumber($request, $request->location_id, $request->receiving_date);
            $PurchaseOrderReceiving = PurchaseOrderReceiving::create([
                'purchase_order_receiving_no' => $grn,
                'purchase_request_id' => $request->purchase_request_id,
                'purchase_order_id' => $request->purchase_order_id,
                'order_receiving_date' => $request->receiving_date,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'truck_no' => $request->truck_no,
                "dc_no" => $request->dc_no,
                'created_by' => auth()->user()->id,
            ]);
            $grnNumber = GrnNumber::create([
                'model_id' => $PurchaseOrderReceiving->id,
                'model_type' => 'purchase-order-receiving',
                'location_id' => $request->location_id,
                'unique_no' => $grn
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
                    'receive_weight' => $request->receive_weight[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                $price = ($request->qty[$index] ?? 0) * ($requestData->purchase_order_data->rate ?? 0);
                
                Stock::create([
                    'product_id' => $itemId,
                    'voucher_type' => 'grn',
                    'voucher_no' => $grn,
                    'qty' => $request->qty[$index] ?? 0,
                    'type' => 'stock-in',
                    'narration' => 'Goods Received Note',
                    'price' => $price,
                    'avg_price_per_kg' => $price,
                    'company_location_id' => $request->company_id,
                    'parent_id' => $request->purchase_order_data_id[$index] ?? null
                ]);

                $product = Product::select("id", "account_id")->find($itemId);
                
                createTransaction(
                    $price,
                    $product->account_id,
                    8,
                    $grnNumber->unique_no,
                    'debit',
                    'no',
                    [
                        'payment_against' => "Goods Received Note",
                        'remarks' => "Goods Received Note"
                    ]  
                );

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
        // $job_orders = JobOrder::select('id', 'name')->get();
        $purchaseOrder = PurchaseOrder::select('id', 'purchase_order_no')->get();
        $location_ids = $purchaseOrderReceiving?->purchase_request?->locations?->pluck("location_id")->toArray();
        // $data = PurchaseOrderReceivingData::with('purchase_order_receiving', 'category', 'item')
        //     ->findOrFail($id);

        $company_locations = CompanyLocation::select("id", "name")->whereIn("id", $location_ids)->get();
        $location_dropdowns = [];

        foreach($company_locations as $company_location) {
            $location_dropdowns[] = [
                "id" => $company_location->id,
                "text" => $company_location->name
            ];
        }


        $purchaseOrderReceivingDataCount = $purchaseOrderReceiving->purchaseOrderReceivingData->count();

        return view('management.procurement.store.purchase_order_receiving.edit', compact('location_dropdowns', 'purchaseOrderReceiving', 'categories', 'locations', 'purchaseOrderReceivingDataCount', 'purchaseOrder'));
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
            'truck_no' => "required",
            "dc_no" => "required",
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

            'receive_weight' => 'nullable|array',
            'receive_weight.*' => 'nullable|string|max:1000',

        ]);




        DB::beginTransaction();
        try {
            $PurchaseOrderReceiving = PurchaseOrderReceiving::findOrFail($id);
            $PurchaseOrderReceiving->update([
                "truck_no" => $request->truck_no,
                "dc_no" => $request->dc_no,
                "description" => $request->description,
                "location_id" => $request->location_id
            ]);

            $grn_data = PurchaseOrderReceivingData::whereDoesntHave("qc")->where('purchase_order_receiving_id', $PurchaseOrderReceiving->id)->delete();


            foreach ($request->item_id as $index => $itemId) {
                if($request->approval_status[$index] === '1') continue;
                PurchaseOrderReceivingData::create([
                    'purchase_order_receiving_id' => $PurchaseOrderReceiving->id,
                    'category_id' => $request->category_id[$index],
                    'purchase_order_data_id' => $request->purchase_order_data_id[$index] ?? null,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index] ?? 0,
                    'rate' => $request->rate[$index] ?? 0,
                    'total' => $request->total[$index] ?? 0,
                    'supplier_id' => $request->supplier_id,
                    'receive_weight' => $request->receive_weight[$index],
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
        $PurchaseOrderReceivingData = PurchaseOrderReceivingData::where('purchase_order_receiving_id', $id)->get();

        foreach($PurchaseOrderReceivingData as $data) {
            if(!$data->qc) {
                $data->delete();
            } 
        }

        $PurchaseOrderReceiving = PurchaseOrderReceiving::where('id', $id)->first();
        if(!$PurchaseOrderReceiving->purchaseOrderReceivingData()->exists()) {
            $PurchaseOrderReceiving->delete();
        }
       
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
        $company_location = CompanyLocation::select("id", "name")->where("id", $purchaseOrderReceiving->location_id)->get();
        return view('management.procurement.store.purchase_order_receiving.approvalCanvas', [
            'purchaseOrderReceiving' => $purchaseOrderReceiving,
            'categories' => $categories,
            'locations' => $locations,
            // 'job_orders' => $job_orders,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderReceivingData' => $purchaseOrderReceivingData,
            'data1' => $purchaseOrderReceiving,
            "locs" => $company_location
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
        // $job_orders = JobOrder::select('id', 'name')->get();

        $html = view('management.procurement.store.purchase_order_receiving.purchase_data', compact('dataItems', 'categories'))->render();

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

        
        // $location = CompanyLocation::find($locationId ?? $request->location_id);
        // $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');
        
        // $locationCode = $location->code ?? 'LOC';
        // $prefix = 'GRN-' . $date;

        // // Find latest PO for the same prefix
        // $latestPO = PurchaseOrderReceiving::withTrashed()->where('purchase_order_receiving_no', 'like', "$prefix-%")
        //     ->orderByDesc('id')
        //     ->first();


        // if ($latestPO) {
        //     // Correct field name
        //     $parts = explode('-', $latestPO->purchase_order_receiving_no);
        //     $lastNumber = (int) end($parts);
        //     $newNumber = $lastNumber + 1;
        // } else {
        //     $newNumber = 1;
        // }
        
        // $purchase_order_receiving_no = 'GRN-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $grn = generateLocationBasedCode('grn_numbers', $location->code);
        
        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_receiving_no' => $grn,
            ]);
        }

        return $grn;
    }
}
