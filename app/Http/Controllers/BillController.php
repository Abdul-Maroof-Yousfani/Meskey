<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillData;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Tax;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index() {
        return view('management.procurement.store.bill.index');
    }
    public function create() {
        $approvedPurchaseOrders = Bill::where('am_approval_status', 'approved')->with([
            'purchaseOrderData' => function ($query) {
                // $query->where('am_approval_status', 'approved');
            }
        ])
            // ->whereHas('purchaseOrderData', function ($q): void {
            //     $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_receiving_data WHERE purchase_order_data_id = purchase_order_data.id)');
            // })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $purchaseRequests = PurchaseRequest::select('id', 'purchase_request_no')->where('am_approval_status', 'approved')->get();

        return view('management.procurement.store.bill.create', compact('categories', 'approvedPurchaseOrders', 'purchaseRequests'));
   
    }
    public function getList() {
        // $PurchaseOrderRaw = PurchaseOrderReceivingData::with(
        //     'qc',
        //     'purchase_order_receiving.purchase_order.purchase_request',
        //     'category',
        //     'item',
        //     'supplier'
        // )
        //     ->latest()
        //     ->paginate(request('per_page', 25));

        $bills = Bill::with(
            "grn",
            "purchase_request",
            "purchase_order"
        )
            ->latest()
            ->paginate(request("per_page", 25));

        $groupedData = [];
        $processedData = [];

        foreach ($bills as $row) {
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
                    // 'qc_status' => $row->qc?->is_qc_approved,
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

        return view('management.procurement.store.bill.getList', [
            'PurchaseOrderReceiving' => $bills,
            'GroupedPurchaseOrderReceiving' => $processedData
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $locationCode = $location->code ?? 'LOC';
        $prefix = 'BILL-' . $locationCode . '-' . $date;

        // Find latest PO for the same prefix
        $latestBill = Bill::where('bill_no', 'like', "$prefix-%")
            ->orderByDesc('id')
            ->first();

        if ($latestBill) {
            // Correct field name
            $parts = explode('-', $latestBill->purchase_order_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $bill_no = 'BILL-' . $locationCode . '-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_no' => $bill_no,
            ]);
        }

        return $bill_no;
    }
    public function getGrns(Request $request) {
        $supplier_id = $request->supplier_id;
        
        $purchase_order_receivings = PurchaseOrderReceiving::select("id", "purchase_order_receiving_no")->where("supplier_id", $supplier_id)->get();

        $results = [];
        foreach ($purchase_order_receivings as $item) {
            $results[] = [
                'id' => $item->purchase_order_receiving_no,
                'text' => $item->purchase_order_receiving_no,
            ];
        }

        return $results;
    }
    public function show() {

    }
    public function approve_item(Request $request) {
        $requestId = $request->id;
        $supplierId = $request->supplier_id;
    

        $master = PurchaseOrderReceiving::where("purchase_order_receiving_no", $requestId)->first();
        $dataItems = collect();
        
        

            $dataItems = PurchaseOrderReceivingData::with(['purchase_request_data', 'item'])
                ->where('purchase_order_receiving_id', $master->id)
                ->get();

       
            $purchaseOrderReceivingDataIds = $dataItems->pluck('id');


        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        // $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        $html = view('management.procurement.store.bill.bills', compact('dataItems', 'categories', 'taxes'))->render();
        return response()->json([
            'html' => $html,
            'master' => $master,
        ]);
    }

    public function store(Request $request) {
        $purchaseOrderReceiving = PurchaseOrderReceiving::where("purchase_order_receiving_no", $request->grn_no)->first();
        $location = $request->company_location;
        $reference_no = $request->reference_no;
        $description = $request->description;
        $items = $request->item_id;
        $descriptions = $request->description;
        $qty = $request->qty;
        $rate = $request->rate;
        $gross_amount = $request->gross_amount;
        $taxes = $request->tax_id;
        $net_amount = $request->net_amount;
        $discounts = $request->discount_id;
        $discount_amounts = $request->discount_amount;
        $deduction = $request->deduction;
        $final_amount = $request->final_amount;

        DB::beginTransaction();

        try {
            $bill = Bill::create([
                "purchase_order_receiving_id" => $purchaseOrderReceiving->id,
                "purchase_request_id" => $purchaseOrderReceiving->purchase_request_id,
                "purchase_order_id" => $purchaseOrderReceiving->purchase_order_id,
                "bill_no" => $reference_no,
                "reference_no" => $reference_no,
                "created_by" => 1,
                "status" => 'active',
                "location_id" => $location,
                "description" => "Description",
                "company_id" => 1,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);
    
            foreach($items as $index => $item) {
                BillData::create([
                    "bill_id" => $bill->id,
                    "item_id" => $items[$index],
                    "purchase_order_receiving_data_id" => $purchaseOrderReceiving->id,
                    "description" => $descriptions[$index],
                    "qty" => $qty[$index],
                    "rate" => $rate[$index],
                    "gross_amount" => $gross_amount[$index],
                    "tax_id" => $taxes[$index],
                    "net_amount" => $net_amount[$index],
                    "discount_percent" => $discounts[$index],
                    "discount_amount" => $discount_amounts[$index],
                    "deduction" => $deduction[$index],
                    "final_amount" => $final_amount[$index]
                ]);
            }

            DB::commit();
            return response()->json("Bill has been created successfully!");
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }


    }
}
