<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\QCRequest;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\QC;
use App\Models\Sales\JobOrder;
use DB;
use Illuminate\Http\Request;

class QcController extends Controller
{
    public function index() {
        return view("management.procurement.store.qc.index");
    }
    public function getList(Request $request) {
        
        $PurchaseOrderRaw = PurchaseOrderReceivingData::whereHas("qc", function($query) {
            return $query->where("is_qc_approved", 0);
        })
            ->latest()
            ->paginate(request("per_page", 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseOrderRaw as $row) {
            // Handle missing relationships
            $purchaseRequestNo = $row->purchase_order_receiving->purchase_quotation->purchase_request->purchase_request_no ?? 'N/A';
            $quotationNo = $row->purchase_order_receiving->purchase_quotation->purchase_quotation_no ?? 'N/A';
            $orderNo = $row->purchase_order_receiving->purchase_order_receiving_no ?? 'N/A';
            $itemId = $row->item->id ?? 'unknown';
            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;
            $purchase_order_receving_id = $row->id;

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
                        'has_approved_item' => $hasApprovedItem
                    ];
                    // dd($processedData);
                }
            }
        }

        // dd(array_keys($groupedData)); // Debug to check all POs

        return view('management.procurement.store.qc.getList', [
            'PurchaseOrderReceiving' => $PurchaseOrderRaw,
            'GroupedPurchaseOrderReceiving' => $processedData
        ]);
    }
    public function show(Request $request) {
        $id = $request->id;
        $grn = $request->grn;

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data")->find($id);


        return view("management.procurement.store.qc.view", compact("grn", "purchaseOrderReceivingData", "id"));
    }
    public function edit(Request $request) {
        $id = $request->id;
        $grn = $request->grn;

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data")->find($id);


        return view("management.procurement.store.qc.edit", compact("grn", "purchaseOrderReceivingData", "id"));
    }
    public function create(Request $request) {
        $id = $request->id;
        $grn = $request->grn;

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data")->find($id);


        return view("management.procurement.store.qc.create", compact("grn", "purchaseOrderReceivingData", "id"));
    }
    public function getForm($id) {
          $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

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
        return view("management.procurement.store.purchase_order_receiving.qc", [
            'purchaseOrderReceiving' => $purchaseOrderReceiving,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderReceivingData' => $purchaseOrderReceivingData,
            'data1' => $purchaseOrderReceiving,
        ]);
    } 
    public function store(QCRequest $request) {
        $net_weights = $request->net_weight;
        $bag_weights = $request->bag_weight;
        $id = $request->purchase_receiving_data_id;

        
        $purchase_receiving_data = PurchaseOrderReceivingData::find($id);
        $purchase_receiving_data->qc()->create([...$request->validated(), "deduction_per_bag" => $request->deduction_per_bag]);
        
        foreach($net_weights as $index => $net_weight) {
            if(is_null($net_weights[$index]) || is_null($bag_weights[$index])) continue;
            $purchase_receiving_data->qc->bags()->create([
                "net_weight" => $net_weights[$index],
                "bag_weight" => $bag_weights[$index]
            ]);
        }

        return response()->json(["qc has been stored"], 200);
    }
    public function update(QCRequest $request) {
        $net_weights = $request->net_weight;
        $bag_weights = $request->bag_weight;
        $id = $request->purchase_receiving_data_id;

        $purchase_receiving_data = PurchaseOrderReceivingData::find($id);
        $purchase_receiving_data->qc()->update([...$request->validated(), "deduction_per_bag" => $request->deduction_per_bag]);
        $purchase_receiving_data->qc->bags()->delete();

        foreach($net_weights as $index => $net_weight) {
            if(is_null($net_weights[$index]) || is_null($bag_weights[$index])) continue;
            $purchase_receiving_data->qc->bags()->create([
                "net_weight" => $net_weights[$index],
                "bag_weight" => $bag_weights[$index]
            ]);
        }

        return response()->json(["qc has been stored"], 200);
    }
    public function destroy(int $id) {
        $purchase_receiving_data = PurchaseOrderReceivingData::find($id);
        
        $purchase_receiving_data->qc()->delete();

        return response()->json("Qc has been deleted!");
    }
}
