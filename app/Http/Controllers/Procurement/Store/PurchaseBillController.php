<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseBillRequest;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Tax;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\Store\PurchaseBillData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseRequest;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class PurchaseBillController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase-bill.index');
    }

    public function create()
    {
        $approvedPurchaseOrders = PurchaseBill::where('am_approval_status', 'approved')
            ->with([
                'purchaseOrderData' => function ($query) {
                    // $query->where('am_approval_status', 'approved');
                },
            ])
            ->whereHas('bill_data', function ($q): void {
                $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_bills_data WHERE purchase_bills_data.purchase_bill_id = purchase_bills.id)');
            })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $purchaseRequests = PurchaseRequest::select('id', 'purchase_request_no')->where('am_approval_status', 'approved')->get();

        return view('management.procurement.store.purchase-bill.create', compact('categories', 'approvedPurchaseOrders', 'purchaseRequests'));
    }

    public function edit(Request $request, int $id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        // $job_orders = JobOrder::get();
        // dd($job_orders);

        $purchase_bill = PurchaseBill::with(['bill_data', 'grn'])->findOrFail($id);

        $purchaseBillData = PurchaseBillData::with("PurchaseOrderReceivingData.purchase_order_data")->where('purchase_bill_id', $id)
            ->when($purchase_bill->am_approval_status === 'approved', function ($query) {
                $query->where('am_approval_status', 'approved');
            })
            ->get();

        $taxes = Tax::all();

        return view('management.procurement.store.purchase-bill.edit', [
            'purchase_bill' => $purchase_bill,
            'categories' => $categories,
            'locations' => $locations,
            'taxes' => $taxes,
            // 'job_orders' => $job_orders,
            'purchaseBillData' => $purchaseBillData,
            'data1' => $purchase_bill,
        ]);
    }

    public function getList()
    {
        // $PurchaseOrderRaw = PurchaseOrderReceivingData::with(
        //     'qc',
        //     'purchase_order_receiving.purchase_order.purchase_request',
        //     'category',
        //     'item',
        //     'supplier'
        // )
        //     ->latest()
        //     ->paginate(request('per_page', 25));

        $bills = PurchaseBill::with(['bill_data', 'grn'])
            ->latest()
            ->paginate(request('per_page', 25));

        $groupedData = [];
        $processedData = [];

        foreach ($bills as $row) {
            // Request-level identifiers
            $purchaseOrderReceivingNo = $row->grn->purchase_order_receiving_no ?? 'N/A';
            $orderNo = $row->bill_no ?? 'N/A';

            if ($orderNo === 'N/A') {
                continue;
            }

            $supplierKey = ($row->supplier->id ?? 'unknown').'_'.$row->id;

            // Initialize main order group
            if (! isset($groupedData[$orderNo])) {
                $groupedData[$orderNo] = [
                    'request_data' => $row->id,
                    'purchase_order_receiving_no' => [],
                ];
            }

            // Initialize receiving number group
            if (! isset($groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo])) {
                $groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo] = [
                    'quotation_data' => $row->purchase_order_receiving->purchase_quotation ?? null,
                    'orders' => [],
                ];
            }

            // Initialize specific order group
            if (! isset($groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo]['orders'][$orderNo])) {
                $groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo]['orders'][$orderNo] = [
                    'order_data' => $row->grn,
                    'row_data' => $row,
                    'approval_status' => $row->am_approval_status,
                    'row' => $row,
                    'items' => [],
                ];
            }

            /*
     |--------------------------------------------------------------------------
     |  FIXED PART: ADD BILL DATA BASED ON item_id
     |--------------------------------------------------------------------------
     |  Each bill has multiple bill_data.
     |  We now iterate OVER bill_data and use item_id as key.
     */

            foreach ($row->bill_data as $billItem) {
                $itemId = $billItem->item_id;
                // Create item group
                if (! isset($groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo]['orders'][$orderNo]['items'][$itemId])) {
                    $groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo]['orders'][$orderNo]['items'][$itemId] = [
                        'item_data' => $billItem,
                        'suppliers' => [],
                    ];
                }

                // Add supplier under that item
                $groupedData[$orderNo]['purchase_order_receiving_no'][$purchaseOrderReceivingNo]['orders'][$orderNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
            }
        }

        /*
 |--------------------------------------------------------------------------
 |  PROCESS GROUPED DATA (unchanged)
 |--------------------------------------------------------------------------
*/
        foreach ($groupedData as $purchaseRequestNo => $requestGroup) {
            foreach ($requestGroup['purchase_order_receiving_no'] as $purchaseOrderReceivingNo => $quotationGroup) {
                foreach ($quotationGroup['orders'] as $orderNo => $orderGroup) {
                    $requestRowspan = 0;
                    $requestItems = [];
                    $hasApprovedItem = false;

                    // Check approved items
                    foreach ($orderGroup['items'] as $itemGroup) {
                        foreach ($itemGroup['suppliers'] as $supplierData) {
                            $approvalColumn = $supplierData->getApprovalModule()->approval_column ?? 'am_approval_status';
                            $approvalStatus = strtolower($supplierData->{$approvalColumn} ?? 'N/A');

                            if ($approvalStatus === 'approved') {
                                $hasApprovedItem = true;
                                break 2;
                            }
                        }
                    }

                    // Build final items
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
                            'item_rowspan' => $itemRowspan,
                        ];
                    }

                    $originalPurchaseRequestNo = $orderGroup['order_data']->purchase_order_receiving_no ?? 'N/A';
                    $originalPurchaseOrderNo = $orderGroup['order_data']->purchase_order->purchase_order_no ?? 'N/A';

                    $processedData[] = [
                        'request_data' => $orderGroup['order_data'],
                        'row_data' => $orderGroup['row_data'],
                        'request_no' => $orderNo,
                        'purchase_request_no' => $originalPurchaseRequestNo,
                        'purchase_order_no' => $originalPurchaseOrderNo,
                        'quotation_no' => $purchaseOrderReceivingNo,
                        'created_by_id' => $orderGroup['order_data']->created_by ?? null,
                        'request_status' => $orderGroup['approval_status'] ?? 'N/A',
                        'request_rowspan' => $requestRowspan,
                        'items' => $requestItems,
                        'has_approved_item' => $hasApprovedItem,
                    ];
                }
            }
        }

        return view('management.procurement.store.purchase-bill.getList', [
            'PurchaseOrderReceiving' => $bills,
            'GroupedPurchaseOrderReceiving' => $processedData,
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $locationCode = $location->code ?? 'LOC';
        $prefix = 'BILL-' . $date;

        // Find latest PO for the same prefix
        $latestBill = PurchaseBill::where('bill_no', 'like', "$prefix-%")
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

        $bill_no = 'BILL-'.$date.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_no' => $bill_no,
            ]);
        }

        return $bill_no;
    }

    public function getGrns(Request $request)
    {
        $supplier_id = $request->supplier_id;

        $purchase_order_receivings = PurchaseOrderReceiving::whereHas('purchaseOrderReceivingData.qc', function ($query) {
            $query->where('am_approval_status', 'approved');
        })
            ->select('id', 'purchase_order_receiving_no')
            ->where('supplier_id', $supplier_id)
            ->get()
            ->filter(function ($data) {
                $purchase_order_receiving_data = PurchaseOrderReceivingData::withCount(['qc' => function ($query) {
                    $query->where('am_approval_status', 'approved');
                }])->where('purchase_order_receiving_id', $data->id)->get();

                $ids = $purchase_order_receiving_data->pluck('id');

                $bills_count = PurchaseBillData::whereIn('purchase_order_receiving_data_id', $ids)->count();

                return $bills_count != $purchase_order_receiving_data->sum('qc_count');
            });

        $results = [];
        foreach ($purchase_order_receivings as $item) {
            $results[] = [
                'id' => $item->purchase_order_receiving_no,
                'text' => $item->purchase_order_receiving_no,
            ];
        }

        return $results;
    }

    public function show() {}

    public function approve_item(Request $request)
    {
        $requestId = $request->id;
        $supplierId = $request->supplier_id;

        $master = PurchaseOrderReceiving::with("purchase_request")->where('purchase_order_receiving_no', $requestId)->first();
        $locations = $master?->purchase_request?->locations;
        $location_ids = $locations->pluck("location_id")->toArray();
        
        $dataItems = collect();

        $dataItems = PurchaseOrderReceivingData::whereHas('qc', function ($query) {
            $query->where('am_approval_status', 'approved');
        })
            ->whereDoesntHave('bill')
            ->with(['purchase_request_data', 'item', 'purchase_order_data'])
            ->where('purchase_order_receiving_id', $master->id)
            ->get();

        // $dataItems = $dataItems->reject(function($datum) {
        //     return totalBillQuantityCreated($datum->purchase_order_receiving_id, $datum->item_id) <= 0;
        // });

        $dataItems = $dataItems->map(function ($datum) {
            $datum->sales_tax = $datum->purchase_order_data->tax_id;

            return $datum;
        });

        $purchaseOrderReceivingDataIds = $dataItems->pluck('id');

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        // $job_orders = JobOrder::select('id', 'job_order_no')->get();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        $html = view('management.procurement.store.purchase-bill.bills', compact('dataItems', 'categories', 'taxes'))->render();

        return response()->json([
            'html' => $html,
            'master' => $master,
            "location_ids" => $location_ids
        ]);
    }

    public function update(PurchaseBillRequest $request, PurchaseBill $purchaseBill)
    {
        $purchaseOrderReceiving = PurchaseOrderReceiving::where('purchase_order_receiving_no', $request->grn_no)->first();
        $location = $request->company_location;
        $reference_no = $request->reference_no;
        $description = $request->purchase_bill_description ?? "";
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
        $tax_amount = $request->tax_amount;
        $purchase_order_receiving_data_id = $request->purchase_order_receiving_data_id;
        $deduction_per_piece = $request->deduction_per_piece;

        DB::beginTransaction();

        try {
            $purchaseBill->update([
                'purchase_order_receiving_id' => $purchaseOrderReceiving->id,
                'bill_no' => $reference_no,
                'reference_no' => $reference_no,
                'created_by' => auth()->user()->id,
                'status' => 'active',
                'location_id' => $location,
                'description' => $description,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
            ]);

            $purchaseBill->bill_data()->delete();

            foreach ($items as $index => $item) {
                $purchaseBill->bill_data()->create([
                    'item_id' => $items[$index],
                    'purchase_order_receiving_data_id' => $purchase_order_receiving_data_id[$index],
                    'description' => $descriptions[$index],
                    'qty' => $qty[$index],
                    'rate' => $rate[$index],
                    'gross_amount' => $gross_amount[$index],
                    'tax_percent' => $taxes[$index],
                    'tax_amount' => $tax_amount[$index],
                    'deduction_per_piece' => $deduction_per_piece[$index],
                    'net_amount' => $net_amount[$index],
                    'discount_percent' => $discounts[$index],
                    'discount_amount' => $discount_amounts[$index],
                    'deduction' => $deduction[$index],
                    'final_amount' => $final_amount[$index],
                    'am_approval_status' => 'pending',
                    'am_change_mode' => 1,
                ]);
            }

            DB::commit();

            return response()->json('Bill has been created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function store(PurchaseBillRequest $request)
    {
        $purchaseOrderReceiving = PurchaseOrderReceiving::where('purchase_order_receiving_no', $request->grn_no)->first();
        $location = $request->company_location;
        $reference_no = $request->reference_no;
        $description = $request->purchase_bill_description ?? "";
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
        $bill_date = $request->purchase_bill_date;
        $supplier_id = $request->supplier_id;
        $tax_amount = $request->tax_amount;
        $deduction_per_piece = $request->deduction_per_piece;
        $purchase_order_receiving_data_id = $request->purchase_order_receiving_data_id;

        DB::beginTransaction();

        try {
            $purchase_bill = PurchaseBill::create([
                'purchase_order_receiving_id' => $purchaseOrderReceiving->id,
                'supplier_id' => $supplier_id,
                // "purchase_request_id" => $purchaseOrderReceiving->purchase_request_id,
                // "purchase_order_id" => $purchaseOrderReceiving->purchase_order_id,
                'bill_no' => $reference_no,
                'reference_no' => $reference_no,
                'created_by' => auth()->user()->id,
                'status' => 'active',
                'location_id' => $location,
                'description' => $description,
                'company_id' => 1,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
                'bill_date' => $bill_date,
            ]);

            foreach ($items as $index => $item) {
                $purchase_bill->bill_data()->create([
                    'item_id' => $items[$index],
                    'purchase_order_receiving_data_id' => $purchase_order_receiving_data_id[$index],
                    'description' => $descriptions[$index],
                    'qty' => $qty[$index],
                    'rate' => $rate[$index],
                    'gross_amount' => $gross_amount[$index],
                    'tax_percent' => $taxes[$index],
                    'tax_amount' => $tax_amount[$index],
                    'deduction_per_piece' => $deduction_per_piece[$index],
                    'net_amount' => $net_amount[$index],
                    'discount_percent' => $discounts[$index],
                    'discount_amount' => $discount_amounts[$index],
                    'deduction' => $deduction[$index],
                    'final_amount' => $final_amount[$index],
                    'am_approval_status' => 'pending',
                    'am_change_mode' => 1,
                ]);
            }

            DB::commit();

            return response()->json('Bill has been created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function manageApprovals(int $id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        // $job_orders = JobOrder::get();
        // dd($job_orders);

        $purchase_bill = PurchaseBill::with(['bill_data', 'grn'])->findOrFail($id);

        $purchaseBillData = PurchaseBillData::with("PurchaseOrderReceivingData.purchase_order_data")->where('purchase_bill_id', $id)
            ->when($purchase_bill->am_approval_status === 'approved', function ($query) {
                // $query->where('am_approval_status', 'approved');
            })
            ->get();

        $taxes = Tax::all();

        return view('management.procurement.store.purchase-bill.view', [
            'purchase_bill' => $purchase_bill,
            'categories' => $categories,
            'locations' => $locations,
            'taxes' => $taxes,
            'purchaseBillData' => $purchaseBillData,
            'data1' => $purchase_bill,
        ]);
    }

    public function destroy(PurchaseBill $purchase_bill)
    {
        $purchase_bill->bill_data()->delete();
        $purchase_bill->delete();

        return response()->json('Purchase bill has been deleted!');
    }
}
