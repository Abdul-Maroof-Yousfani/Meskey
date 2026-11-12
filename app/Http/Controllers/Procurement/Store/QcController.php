<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\QCAmountRequest;
use App\Http\Requests\QCRequest;
use App\Models\Category;
use App\Models\Master\Account\Stock;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseBagQC;
use App\Models\Sales\JobOrder;
use DB;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalModuleRole;

use Illuminate\Http\Request;

class QcController extends Controller
{
    public function index() {
        return view("management.procurement.store.qc.index");
    }

    public function purchaseQcIndex() {
        $categories = Category::where('category_type', 'general_items')->get();
        return view("management.procurement.store.qc.purchase_qc_index", compact('categories'));
    }

    public function purchaseQcGetList(Request $request)
    {
        $query = PurchaseOrderReceivingData::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('qty', 'like', "%{$search}%")
                    ->orWhere('rate', 'like', "%{$search}%")
                    ->orWhere('total', 'like', "%{$search}%")
                    ->orWhereHas('purchase_order_receiving', function ($q) use ($search) {
                        $q->where('purchase_order_receiving_no', 'like', "%{$search}%")
                            ->orWhere('dc_no', 'like', "%{$search}%")
                            ->orWhereHas('purchase_order', function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%")
                                    ->orWhereHas('purchase_request', function ($q) use ($search) {
                                        $q->where('purchase_request_no', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        if ($request->has('dc_no') && !empty($request->dc_no)) {
            $query->whereHas('purchase_order_receiving', function($q) use ($request) {
                $q->where('dc_no', $request->dc_no);
            });
        }

        foreach (['qty', 'rate', 'total'] as $field) {
            if ($request->has($field) && !empty($request->$field)) {
                if ($field === 'rate' || $field === 'total') {
                    $query->whereHas('purchase_order_data', function ($q) use ($field, $request) {
                        $q->where($field, $request->$field);
                    });
                } else {
                    $query->where($field, $request->$field);
                }
            }
        }

        if ($request->has('qc_status') && !empty($request->qc_status)) {
            $query->whereHas('qc', function($q) use ($request) {
                $status = $request->qc_status;
                if ($status === 'pending') {
                    $q->whereIn('am_approval_status', ['pending', 'reverted']);
                } else {
                    $q->where('am_approval_status', $status);
                }
            });
        }

        $PurchaseOrderRaw = $query->with([
            'qc',
            'purchase_order_data.purchase_request_data',
            'purchase_order_receiving.purchase_order.purchase_request',
            'category',
            'item'
        ])
        ->orderBy("purchase_order_receiving_id", "desc")
        ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($PurchaseOrderRaw as $row) {
            $dc_no = $row->purchase_order_receiving->dc_no ?? null;

            $purchaseRequestNo = $row->purchase_order_receiving->purchase_quotation_data->purchase_request->purchase_request_no ?? 'N/A';
            $quotationNo        = $row->purchase_order_receiving->purchase_quotation_data->purchase_quotation_no ?? 'N/A';
            $orderNo            = $row->purchase_order_receiving->purchase_order_receiving_no ?? 'N/A';

            if ($orderNo === 'N/A') {
                continue;
            }

            $itemId      = $row->item->id ?? 'unknown';
            $dataId      = $row->id;
            $uniqueItemKey = $itemId . '_' . $dataId;

            $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $dataId;

            if (!isset($groupedData[$orderNo])) {
                $groupedData[$orderNo] = [
                    'request_data' => $row->purchase_order_receiving->purchase_quotation_data->purchase_request ?? null,
                    'quotations'   => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo] = [
                    'quotation_data' => $row->purchase_order_receiving->purchase_quotation_data ?? null,
                    'orders'         => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo] = [
                    'order_data' => $row->purchase_order_receiving,
                    'items'      => []
                ];
            }

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey] = [
                    'item_data'  => $row,
                    'item_qc'    => $row->qc,
                    'qc_status'  => $row->qc?->am_approval_status,
                    'suppliers'  => []
                ];
            }

            $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey]['suppliers'][$supplierKey] = $row;
        }

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
                                                                ->whereIn("role_id", auth()->user()->roles->pluck('id'))
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
                        'dc_no' => $orderGroup['order_data']->dc_no ?? 'N/A',
                        'has_approved_item' => $hasApprovedItem,
                    ];
                }
            }
        }

        return view('management.procurement.store.qc.purchase_qc_getList', [
            'PurchaseOrderReceiving' => $PurchaseOrderRaw,
            'GroupedPurchaseOrderReceiving' => $processedData
        ]);
    }
    public function deleteQc(Request $request) {
        $id = $request->id;

        return $this->deleteQcWithPostings(PurchaseBagQC::find($id));
    }
    public function updateAmounts(QCAmountRequest $request) {
        $id  = $request->id;
        $qc = PurchaseBagQC::find($id);

        if (!$qc) {
            return response()->json(["message" => "QC not found."], 404);
        }

        // Stock and ledger are posted from these quantities at approval; they can't change afterwards.
        $status = strtolower($qc->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(["message" => "This QC is already {$status}; quantities cannot be changed."], 422);
        }

        $qc->update($request->validated());

        return response()->json("Qc has been updated");
    }
    public function getList(Request $request) {
        $PurchaseOrderRaw = PurchaseOrderReceivingData::whereHas("qc", function($query) {
            // return $query->filter();
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
            $dataId = $row->id;
            $uniqueItemKey = $itemId . '_' . $dataId;
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

            if (!isset($groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey])) {
                $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey] = [
                    'item_data' => $row,
                    'qc_status' => $row->qc?->am_approval_status,
                    'canUserApprove' => $row->qc?->canUserApprove(),
                    'suppliers' => []
                ];
            }

            $groupedData[$orderNo]['quotations'][$quotationNo]['orders'][$orderNo]['items'][$uniqueItemKey]['suppliers'][$supplierKey] = $row;
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
                            'qc_status' => $itemGroup["qc_status"],
                            'canUserApprove' => $itemGroup["canUserApprove"],
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
        $type = $request->type;

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data")->find($id);

        $job_orders = [];

        foreach($purchaseOrderReceivingData->purchase_order_data->job_orders as $j_order) {
            $job_orders[] = $j_order->job_order_data->job_order_no;
        }

        return view("management.procurement.store.qc.view", compact("grn", "purchaseOrderReceivingData", "id", "type", "job_orders"));
    }
    public function edit(Request $request) {
        $id = $request->id;
        $grn = $request->grn;
        $refresh_url = $request->refresh_url;


        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data")->find($id);


        return view("management.procurement.store.qc.edit", compact("grn", "purchaseOrderReceivingData", "id", "refresh_url"));
    }
    public function create(Request $request) {
        $id = $request->id;
        $grn = $request->grn;
     

        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("qc", "purchase_order_data", 'purchase_order_data.purchase_request_data.JobOrder')->find($id);
        

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
        // $stock = Stock::where("product_id", $id)->where("voucher_type", "grn")->first();
        // $stock->qty = $request->accepted_quantity;
        // $stock->save();

        
        $purchase_receiving_data = PurchaseOrderReceivingData::with('purchase_order_data')->find($id);

        if (!$purchase_receiving_data) {
            return response()->json(["message" => "GRN item not found."], 404);
        }

        if ($purchase_receiving_data->qc()->exists()) {
            return response()->json(["message" => "QC already exists for this GRN item. Please edit it instead."], 422);
        }

        $isBag = $purchase_receiving_data->category_id == 38;
        $is_auto_approval = $this->isQcAutoApprovable($request, $purchase_receiving_data);

        DB::transaction(function () use ($request, $purchase_receiving_data, $isBag, $is_auto_approval, $net_weights, $bag_weights) {
            $qc = $purchase_receiving_data->qc()->create([
                ...collect($request->validated())->except("net_weight", "bag_weight"),
                "deduction_per_bag" => $request->deduction_per_bag ?? 0,
            ]);

            $this->saveQcBags($qc, $isBag, $net_weights, $bag_weights);

            if ($is_auto_approval) {
                $this->autoApproveQc($qc);
            }
        });

        return response()->json(["qc has been stored"], 200);
    }

    /**
     * Auto-approval rules (same for create and edit):
     * - Bags (category 38): sample average weight >= PO min weight - tolerance,
     *   printing + bottom stitching + ready to pack all OK, and no rejected qty.
     * - Other items: no rejected qty.
     */
    private function isQcAutoApprovable(QCRequest $request, PurchaseOrderReceivingData $grnLine): bool
    {
        $noRejection = (float) $request->rejected_quantity == 0;

        if ($grnLine->category_id != 38) {
            return $noRejection;
        }

        $min_weight = (float) ($grnLine->purchase_order_data->min_weight ?? 0);
        $allowed_value = $min_weight - (float) $grnLine->tolerance;

        return ((float) $request->sample_average_weight >= $allowed_value)
            && areQcParametersOk($request)
            && $noRejection;
    }

    private function autoApproveQc(PurchaseBagQC $qc): void
    {
        // Ledger can't be posted (e.g. item has no inventory account): leave it pending for manual approval.
        if ($qc->validateBeforeApproval()) {
            return;
        }

        $qc->am_approval_status = "approved";
        $qc->is_auto_approved = 1;
        $qc->am_change_made = 1;
        $qc->save();

        $qc->postApproval();
    }

    /**
     * An approved QC can only be removed while nothing was built on it (no bill, no rejection return);
     * its stock-in and GRN accrual are reversed with it.
     */
    private function deleteQcWithPostings(?PurchaseBagQC $qc)
    {
        if (!$qc) {
            return response()->json(["message" => "QC not found."], 404);
        }

        $grnLine = $qc->grn;
        if ($grnLine && $grnLine->bill()->exists()) {
            return response()->json(["message" => "A purchase bill exists for this GRN item. Delete the bill first."], 422);
        }
        if ($grnLine && \App\Models\Procurement\Store\RejectionReturnItem::where('item_id', $grnLine->item_id)
                ->whereHas('rejectionReturn', fn ($q) => $q->where('grn_id', $grnLine->purchase_order_receiving_id))
                ->exists()) {
            return response()->json(["message" => "A rejection return exists for this GRN item. Delete it first."], 422);
        }

        DB::transaction(function () use ($qc) {
            app(\App\Services\StoreLedgerService::class)->reverseQc($qc);
            $qc->bags()->delete();
            $qc->delete();
        });

        return response()->json("Purchase Bag Qc has been deleted!");
    }

    private function saveQcBags(PurchaseBagQC $qc, bool $isBag, $net_weights, $bag_weights): void
    {
        if (!$isBag || !$net_weights) {
            return;
        }

        foreach ($net_weights as $index => $net_weight) {
            if (is_null($net_weight) || is_null($bag_weights[$index] ?? null)) continue;
            $qc->bags()->create([
                "net_weight" => $net_weight,
                "bag_weight" => $bag_weights[$index],
            ]);
        }
    }
    public function update(QCRequest $request) {
        $net_weights = $request->net_weight;
        $bag_weights = $request->bag_weight;
        $id = $request->purchase_receiving_data_id;


        $purchase_receiving_data = PurchaseOrderReceivingData::with('purchase_order_data', 'qc')->find($id);
        $qc = $purchase_receiving_data?->qc;

        if (!$qc) {
            return response()->json(["message" => "QC not found for this GRN item."], 404);
        }

        $currentStatus = strtolower($qc->am_approval_status ?? 'pending');
        if (in_array($currentStatus, ['approved', 'rejected'])) {
            return response()->json(["message" => "This QC is already {$currentStatus} and cannot be edited."], 422);
        }

        $isBag = $purchase_receiving_data->category_id == 38;
        $is_auto_approval = $this->isQcAutoApprovable($request, $purchase_receiving_data);

        DB::transaction(function () use ($request, $qc, $isBag, $is_auto_approval, $net_weights, $bag_weights) {
            $qc->update([
                ...collect($request->validated())->except("net_weight", "bag_weight"),
                "deduction_per_bag" => $request->deduction_per_bag ?? 0,
                // The QC form only splits accepted/rejected; the approver re-splits
                // rejected into with/without deduction on the approval screen.
                "rejection_return" => 0,
                // An edited QC (including a reverted one) goes back to the approver as pending.
                "am_approval_status" => "pending",
                "am_change_made" => 1,
            ]);

            $qc->bags()->delete();
            $this->saveQcBags($qc, $isBag, $net_weights, $bag_weights);

            if ($is_auto_approval) {
                $this->autoApproveQc($qc);
            }
        });

        return response()->json(["qc has been updated"], 200);
    }
    public function destroy(int $id) {
        $purchase_receiving_data = PurchaseOrderReceivingData::find($id);

        return $this->deleteQcWithPostings($purchase_receiving_data?->qc);
    }
}
