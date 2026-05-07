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
use App\Models\Master\Supplier;
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
        $quotedItemIds = PurchaseQuotationData::distinct()->pluck('item_id');
        $quotedSupplierIds = PurchaseQuotationData::distinct()->pluck('supplier_id');
        $quotedUomIds = Product::whereIn('id', $quotedItemIds)->distinct()->pluck('unit_of_measure_id');

        $suppliers = Supplier::whereIn('id', $quotedSupplierIds)->select('id', 'name')->where('status', 'active')->whereType('store_supplier')->get();
        $items = Product::whereIn('id', $quotedItemIds)->select('id', 'name')->where('status', 'active')->get();
        $uoms = \App\Models\UnitOfMeasure::whereIn('id', $quotedUomIds)->select('id', 'name')->where('status', 'active')->get();
        
        return view('management.procurement.store.purchase_quotation.index', compact('suppliers', 'items', 'uoms'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        // $PurchaseQuotationRaw = PurchaseQuotationData::with(
        //     'purchase_quotation.purchase_request',
        //     'category',
        //     'item',
        //     'supplier'
        // )
        //     ->whereStatus(true)
        //     ->latest()
        //     ->paginate(request('per_page', 25));

        // $groupedData = [];
        // $processedData = [];

        // foreach ($PurchaseQuotationRaw as $row) {
        //     if (!$row->purchase_quotation || !$row->purchase_quotation->purchase_request) {
        //         continue;
        //     }

        //     $purchaseRequestNo = $row->purchase_quotation->purchase_request->purchase_request_no;
        //     $requestNo = $row->purchase_quotation->purchase_quotation_no; // purchase quotation no
        //     $itemId = $row->item->id ?? 'unknown';
        //     $supplierKey = ($row->supplier->id ?? 'unknown') . '_' . $row->id;

        //     // Group by purchase_request_no → purchase_quotation_no → item_id → suppliers
        //     if (!isset($groupedData[$purchaseRequestNo])) {
        //         $groupedData[$purchaseRequestNo] = [
        //             'request_data' => $row->purchase_quotation->purchase_request,
        //             'quotations' => []
        //         ];
        //     }

        //     if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo])) {
        //         $groupedData[$purchaseRequestNo]['quotations'][$requestNo] = [
        //             'quotation_data' => $row->purchase_quotation,
        //             'items' => []
        //         ];
        //     }

        //     if (!isset($groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId])) {
        //         $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId] = [
        //             'item_data' => $row,
        //             'suppliers' => []
        //         ];
        //     }

        //     $groupedData[$purchaseRequestNo]['quotations'][$requestNo]['items'][$itemId]['suppliers'][$supplierKey] = $row;
        // }

        // // Build $processedData while preserving your structure
        // foreach ($groupedData as $purchaseRequestNo => $requestGroup) {
        //     foreach ($requestGroup['quotations'] as $quotationNo => $quotationGroup) {
        //         $requestRowspan = 0;
        //         $requestItems = [];
        //         $hasApprovedItem = false;

        //         foreach ($quotationGroup['items'] as $itemGroup) {
        //             foreach ($itemGroup['suppliers'] as $supplierData) {
        //                 $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
        //                 if (strtolower($approvalStatus) === 'approved') {
        //                     $hasApprovedItem = true;
        //                     break 2;
        //                 }
        //             }
        //         }

        //         foreach ($quotationGroup['items'] as $itemId => $itemGroup) {
        //             $itemRowspan = count($itemGroup['suppliers']);
        //             $requestRowspan += $itemRowspan;

        //             $itemSuppliers = [];
        //             $isFirstSupplier = true;

        //             foreach ($itemGroup['suppliers'] as $supplierKey => $supplierData) {
        //                 $itemSuppliers[] = [
        //                     'data' => $supplierData,
        //                     'is_first_supplier' => $isFirstSupplier,
        //                     'item_rowspan' => $itemRowspan
        //                 ];
        //                 $isFirstSupplier = false;
        //             }

        //             $requestItems[] = [
        //                 'item_data' => $itemGroup['item_data'],
        //                 'suppliers' => $itemSuppliers,
        //                 'item_rowspan' => $itemRowspan
        //             ];
        //         }

        //         $processedData[] = [
        //             'request_data' => $quotationGroup['quotation_data'],
        //             'request_no' => $quotationNo,
        //             'purchase_request_no' => $purchaseRequestNo,
        //             'created_by_id' => $quotationGroup['quotation_data']->created_by,
        //             'request_status' => $quotationGroup['quotation_data']->am_approval_status,
        //             'request_rowspan' => $requestRowspan,
        //             'items' => $requestItems,
        //             'has_approved_item' => $hasApprovedItem
        //         ];
        //     }
        // }

        // return view('management.procurement.store.purchase_quotation.getList', [
        //     'PurchaseQuotation' => $PurchaseQuotationRaw,
        //     'GroupedPurchaseQuotation' => $processedData
        // ]);

        $query = PurchaseQuotationData::with(
            'purchase_quotation.purchase_request',
            'category',
            'item',
            'supplier'
        )->whereStatus(true);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qty', 'like', "%{$search}%")
                  ->orWhere('rate', 'like', "%{$search}%")
                  ->orWhere('total', 'like', "%{$search}%")
                  ->orWhereHas('purchase_quotation', function($pq) use ($search) {
                      $pq->where('purchase_quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('purchase_request', function($pr) use ($search) {
                            $pr->where('purchase_request_no', 'like', "%{$search}%");
                        });
                  });
            });
        }

        if ($request->has('pr_no') && !empty($request->pr_no)) {
            $pr_no = $request->pr_no;
            $query->whereHas('purchase_quotation.purchase_request', function($q) use ($pr_no) {
                $q->where('purchase_request_no', 'like', "%{$pr_no}%");
            });
        }

        if ($request->has('pq_no') && !empty($request->pq_no)) {
            $pq_no = $request->pq_no;
            $query->whereHas('purchase_quotation', function($q) use ($pq_no) {
                $q->where('purchase_quotation_no', 'like', "%{$pq_no}%");
            });
        }

        if ($request->has('supplier_id') && $request->supplier_id != 'all' && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }



        if ($request->has('item_id') && $request->item_id != 'all' && !empty($request->item_id)) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->has('uom_id') && $request->uom_id != 'all' && !empty($request->uom_id)) {
            $uomId = $request->uom_id;
            $query->whereHas('item', function($q) use ($uomId) {
                $q->where('unit_of_measure_id', $uomId);
            });
        }

        if ($request->has('status') && $request->status != 'all' && !empty($request->status)) {
            $status = $request->status;
            $query->where('am_approval_status', $status);
        }

        $PurchaseQuotationRaw = $query->latest()
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

            if($row->am_approval_status == "reverted" || $row->am_approval_status == "pending") {
                $groupedData[$purchaseRequestNo]["is_editable"] = true;
            } else {
                $groupedData[$purchaseRequestNo]["is_editable"] = false;
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
                $hasPendingOrRevertedItem = false;
                foreach ($quotationGroup['items'] as $itemGroup) {
                    foreach ($itemGroup['suppliers'] as $supplierData) {
                        $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
                        $status = strtolower($approvalStatus);
                        if ($status === 'approved') {
                            $hasApprovedItem = true;
                        }
                        if ($status === 'pending' || $status === 'reverted' || $status === 'neglected') {
                            $hasPendingOrRevertedItem = true;
                        }

                        if ($hasApprovedItem && $hasPendingOrRevertedItem) {
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
                    'has_approved_item' => $hasApprovedItem,
                    'has_pending_or_reverted_item' => $hasPendingOrRevertedItem
                ];

            }
        }

        return view('management.procurement.store.purchase_quotation.getComparison', [
            'PurchaseQuotation' => $PurchaseQuotationRaw,
            'GroupedPurchaseQuotation' => $processedData,
            "groupedData" => $groupedData
        ]);
    }

    public function comparison_list()
    {
        $quotedItemIds = PurchaseQuotationData::distinct()->pluck('item_id');
        $suppliers = Supplier::select('id', 'name')->where('status', 'active')->whereType('store_supplier')->get();
        $items = Product::whereIn('id', $quotedItemIds)->select('id', 'name')->where('status', 'active')->get();
        $uoms = \App\Models\UnitOfMeasure::select('id', 'name')->get();

        return view('management.procurement.store.purchase_quotation.comparisonList', compact('suppliers', 'items', 'uoms'));
    }


    public function get_comparison(Request $request)
    {
        $query = PurchaseQuotationData::with(
            'purchase_quotation.purchase_request',
            'purchase_request',
            'category',
            'item',
            'supplier'
        )->whereStatus(true);


        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qty', 'like', "%{$search}%")
                  ->orWhere('rate', 'like', "%{$search}%")
                  ->orWhere('total', 'like', "%{$search}%")
                  ->orWhereHas('purchase_quotation', function($pq) use ($search) {
                      $pq->where('purchase_quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('purchase_request', function($pr) use ($search) {
                            $pr->where('purchase_request_no', 'like', "%{$search}%");
                        });
                  });
            });
        }

        if ($request->has('pr_no') && !empty($request->pr_no)) {
            $pr_no = $request->pr_no;
            $query->whereHas('purchase_quotation.purchase_request', function($q) use ($pr_no) {
                $q->where('purchase_request_no', 'like', "%{$pr_no}%");
            });
        }

        if ($request->has('pq_no') && !empty($request->pq_no)) {
            $pq_no = $request->pq_no;
            $query->whereHas('purchase_quotation', function($q) use ($pq_no) {
                $q->where('purchase_quotation_no', 'like', "%{$pq_no}%");
            });
        }

        if ($request->has('supplier_id') && $request->supplier_id != 'all' && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('qty') && !empty($request->qty)) {
            $query->where('qty', $request->qty);
        }

        if ($request->has('rate') && !empty($request->rate)) {
            $query->where('rate', $request->rate);
        }

        if ($request->has('amount') && !empty($request->amount)) {
            $query->where('total', $request->amount);
        }



        if ($request->has('item_id') && $request->item_id != 'all' && !empty($request->item_id)) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->has('uom_id') && $request->uom_id != 'all' && !empty($request->uom_id)) {
            $uomId = $request->uom_id;
            $query->whereHas('item', function($q) use ($uomId) {
                $q->where('unit_of_measure_id', $uomId);
            });
        }

        if ($request->has('status') && $request->status != 'all' && !empty($request->status)) {
            $status = $request->status;
            $query->where('am_approval_status', $status);
        }

        $PurchaseQuotationRaw = $query->latest()
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
                $hasPendingOrRevertedItem = false;

                foreach ($quotationGroup['items'] as $itemGroup) {
                    foreach ($itemGroup['suppliers'] as $supplierData) {
                        $approvalStatus = $supplierData->{$supplierData->getApprovalModule()->approval_column ?? 'am_approval_status'};
                        $status = strtolower($approvalStatus);
                        if ($status === 'approved') {
                            $hasApprovedItem = true;
                        }
                        if ($status === 'pending' || $status === 'reverted' || $status === 'neglected') {
                            $hasPendingOrRevertedItem = true;
                        }

                        if ($hasApprovedItem && $hasPendingOrRevertedItem) {
                            break 2;
                        }
                    }
                }
                
                foreach ($quotationGroup['items'] as $itemId => $itemGroup) {
                    $itemRowspan = count($itemGroup['suppliers']);
                    $quotaionCount = count(value: $requestGroup['quotations']);
                    $requestRowspan += $itemRowspan;
                    $quotaionRowspan += ($quotaionCount);

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
                    'has_approved_item' => $hasApprovedItem,
                    'has_pending_or_reverted_item' => $hasPendingOrRevertedItem
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


    public function dataForComparison($purchase_request_id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $purchaseRequest = PurchaseRequest::with([
            'PurchaseData',
            'PurchaseData.category',
            'PurchaseData.item',
        ])->findOrFail($purchase_request_id);

        // dd(count($purchaseRequest->PurchaseData));
        

        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $query = PurchaseQuotationData::with(['purchase_request.JobOrder.job_order_data', 'purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds);

        if (request()->has('supplier_ids') && !empty(request('supplier_ids'))) {
            $query->whereIn('supplier_id', request('supplier_ids'));
        }

        if (request()->has('category_ids') && !empty(request('category_ids'))) {
            $query->whereIn('category_id', request('category_ids'));
        }

        if (request()->has('statuses') && !empty(request('statuses'))) {
            $query->whereIn('am_approval_status', request('statuses'));
        }

        if (request()->has('location_ids') && !empty(request('location_ids'))) {
            $query->whereHas('purchase_quotation', function ($q) {
                $q->whereIn('location_id', request('location_ids'));
            });
        }

        $PurchaseQuotationData = $query->get();

    $groupedItems = [];
    foreach ($PurchaseQuotationData as $row) {
        $prDataId = $row->purchase_request_data_id;
        if (!isset($groupedItems[$prDataId])) {
            $groupedItems[$prDataId] = [
                'pr_data' => $row->purchase_request,
                'quotations' => [],
                'rowspan' => 0
            ];
        }
        $groupedItems[$prDataId]['quotations'][] = $row;
        $groupedItems[$prDataId]['rowspan']++;
    }

    foreach ($groupedItems as &$group) {
        usort($group['quotations'], function ($a, $b) {
            return $a->rate <=> $b->rate;
        });
    }
            
    $data1 = PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
        ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
        ->where('am_approval_status', 'pending')
        ->latest()->first() 
        ?? PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
        ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)
        ->latest()->first();
  
    return view('management.procurement.store.purchase_quotation.dataForComparison', [
        'purchaseRequest' => $purchaseRequest,
        'categories' => $categories,
        'locations' => $locations,
        'job_orders' => $job_orders,
        'groupedItems' => $groupedItems,
        'PurchaseQuotationData' => $PurchaseQuotationData,
        "PurchaseQuotation" => $PurchaseQuotationData->first()->purchase_quotation ?? null,
        'data1' => $data1,
    ]);
    }

    public function manageComparisonApprovals($purchase_request_id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $purchaseRequest = PurchaseRequest::with([
            'PurchaseData',
            'PurchaseData.category',
            'PurchaseData.item',
        ])->findOrFail($purchase_request_id);

        // dd(count($purchaseRequest->PurchaseData));
        

        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
                                                ->whereNotIn("am_approval_status", ["rejected", "approved"])
                                                ->pluck('id');
        $master_data = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)->first();
                                        

        $PurchaseQuotationIds2 = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $PurchaseQuotationData = PurchaseQuotationData::with(['purchase_request.JobOrder.job_order_data', 'purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)

            ->whereNotIn("am_approval_status", ["rejected", "approved", "reverted"])
            // ->where('am_approval_status', 'pending')
            //     ->whereHas('purchase_quotation', function ($query) {
            //     $query->whereNotIn('am_approval_status', ['partial_approved']);
            // })
            ->get();


        $data = PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds2)
            ->whereIn('am_approval_status', ['pending', 'neglected'])
            ->latest()->first() 
            ?? PurchaseQuotationData::with(['purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds2)
            ->latest()->first();
        
      
        return view('management.procurement.store.purchase_quotation.approvalComparisonCanvas', [
            'purchaseRequest' => $purchaseRequest,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'PurchaseQuotationData' => $PurchaseQuotationData,
            "PurchaseQuotation" => $PurchaseQuotationData->first()?->purchase_quotation,
            "master_data" => $master_data,
            'data1' => $data,
        ]);
    }

    public function manageComparisonApprovalsView($purchase_request_id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $purchaseRequest = PurchaseRequest::with([
            'PurchaseData',
            'PurchaseData.category',
            'PurchaseData.item',
        ])->findOrFail($purchase_request_id);


        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $PurchaseQuotationData = PurchaseQuotationData::with(['purchase_request.JobOrder.job_order_data', 'purchase_quotation', 'supplier', 'item', 'category'])
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
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $purchaseQuotation = PurchaseQuotation::with([
            'quotation_data',
            'quotation_data.category',
            'quotation_data.item',
            'quotation_data.supplier'
        ])->findOrFail($id);


        $purchaseQuotationData = PurchaseQuotationData::with(['purchase_request.JobOrder.job_order_data', 'category', 'item', 'supplier'])
        ->where('purchase_quotation_id', $id)
        ->get();


        $pendingData = PurchaseQuotationData::where('purchase_quotation_id', $id)
            ->where('am_approval_status', 'pending')
            ->first();

        return view('management.procurement.store.purchase_quotation.approvalCanvas', [
            'purchaseQuotation' => $purchaseQuotation,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'purchaseQuotationData' => $purchaseQuotationData,
            'data1' => $pendingData ?? $purchaseQuotation,
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

        $master = PurchaseRequest::with("locations")->find($requestId);
        $locations_id = $master->locations->pluck("location_id")->toArray();
        $location_names = [];
        foreach($locations_id as $location_id) {
            $location_names[] = get_location_name_by_id($location_id);
        }

        $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category', 'JobOrder.job_order_data'])
            ->where('purchase_request_id', $requestId)
            ->where('am_approval_status', 'approved')
            ->get();


        $purchaseRequestDataIds = $dataItems->pluck('id');

        $existingQuotationCount = PurchaseQuotationData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
            ->where("am_approval_status", "!=", "rejected")
            // ->where("am_approval_status", 'approved')
            // ->orWhere("am_approval_status", "pending")
            ->whereHas('purchase_quotation', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->count();
        $quantities = [];
        if ($existingQuotationCount > 0) {

            // Important: Group by purchase_request_data_id instead of only item_id
            $quotedQuantities = PurchaseQuotationData::whereIn('purchase_request_data_id', $purchaseRequestDataIds)
                ->where("am_approval_status", "!=", "rejected")
                ->whereHas('purchase_quotation', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->select('purchase_request_data_id', DB::raw('SUM(qty) as total_quoted_qty'))
                ->groupBy('purchase_request_data_id')           // ←←← Yeh change karo
                ->pluck('total_quoted_qty', 'purchase_request_data_id');

            // Ab har item ke liye uski apni row id ke against quoted qty nikaalo
            foreach ($dataItems as $item) {
                $quotedQty = $quotedQuantities[$item->id] ?? 0;     // ←←← item->id use karo, na ke item_id
                $remainingQty = $item->qty - $quotedQty;
                $item->qty = max($remainingQty, 0);
            }

        } else {
            // No previous quotation case
            foreach ($dataItems as $item) {
                $item->qty = $item->qty ?? 0;
            }
        }

        $purchaseRequestDataCount = $dataItems->count();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();
        
        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        $categoryIds = $dataItems->pluck('category_id')->unique()->values();
        $itemIds = $dataItems->pluck('item_id')->unique()->values();

        return response()->json([
            'html' => $html,
            'master' => $master,
            'allowed_categories' => $categoryIds,
            'allowed_items' => $itemIds,
            'purchaseRequestDataCount' => $purchaseRequestDataCount,
            "quantities" => $quantities,
            'locations_id' => $locations_id,
            'location_names' => $location_names
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::with(['PurchaseData' => function($query) {
            $query->where("am_approval_status", "approved");
        }])->whereHas('PurchaseData', function ($q) {
            // $q->where('am_approval_status', 'approved');
            // ->where('quotation_status', 1);
            $q->whereRaw('qty > (SELECT COALESCE(SUM(pod.qty), 0) FROM purchase_order_data pod JOIN purchase_orders po ON po.id = pod.purchase_order_id WHERE pod.purchase_request_data_id = purchase_request_data.id AND pod.am_approval_status != "rejected" AND po.am_approval_status != "rejected")')
                ->where('am_approval_status', 'approved');
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
                    'delivery_date' => $request->delivery_date[$index] ?? null,
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

        $approvedRequests = PurchaseRequest::with(['PurchaseData' => function($query) {
            $query->where("am_approval_status", "approved");
        }])->whereHas('PurchaseData', function ($q) {
            $q->whereRaw('qty > (SELECT COALESCE(SUM(pod.qty), 0) FROM purchase_order_data pod JOIN purchase_orders po ON po.id = pod.purchase_order_id WHERE pod.purchase_request_data_id = purchase_request_data.id AND pod.am_approval_status != "rejected" AND po.am_approval_status != "rejected")')
                ->where('am_approval_status', 'approved');
        })
            ->get();

        $purchase_request_id = request()->purchase_request_id ?? $purchaseQuotation->purchase_request_id;
   
        $PurchaseQuotationIds = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
                                                ->whereIn("am_approval_status", ["pending", "reverted", "partial approved"])
                                                ->pluck('id');

        $PurchaseQuotationIds2 = PurchaseQuotation::where('purchase_request_id', $purchase_request_id)
            ->pluck('id');

        $PurchaseQuotationData = PurchaseQuotationData::with(['purchase_request.JobOrder.job_order_data', 'purchase_quotation', 'supplier', 'item', 'category'])
            ->whereIn('purchase_quotation_id', $PurchaseQuotationIds)

            ->whereIn("am_approval_status", ["pending", "reverted"])
            // ->where('am_approval_status', 'pending')
            //     ->whereHas('purchase_quotation', function ($query) {
            //     $query->whereNotIn('am_approval_status', ['partial_approved']);
            // })
            ->get();

        $purchaseRequest = $purchaseQuotation->purchase_request;
        $locations_id = $purchaseRequest->locations->pluck("location_id")->toArray();
      
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
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $purchaseQuotationDataCount = $purchaseQuotation->quotation_data->count();

        // Calculate total quoted quantities for each PR item to determine max limits (Supplier-wise)
        $pr_data_ids = $purchaseRequest->PurchaseData->pluck('id');
        $all_quoted = \App\Models\Procurement\Store\PurchaseQuotationData::whereIn('purchase_request_data_id', $pr_data_ids)
            ->where('am_approval_status', '!=', 'rejected')
            ->select('purchase_request_data_id', 'supplier_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('purchase_request_data_id', 'supplier_id')
            ->get()
            ->groupBy('purchase_request_data_id')
            ->map(function ($items) {
                return $items->pluck('total_qty', 'supplier_id');
            })
            ->toArray();

        return view('management.procurement.store.purchase_quotation.edit', compact(
            "PurchaseQuotationData",
            'purchaseQuotation',
            'categories',
            'items',
            'locations',
            'job_orders',
            'purchaseQuotationDataCount',
            'locations_id',
            'all_quoted',
            'approvedRequests'
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
        $locations_id = $master->locations->pluck("location_id")->toArray();
       

        $dataItems = PurchaseQuotationData::with(['purchase_quotation', 'item', 'category'])
            ->where('purchase_quotation_id', $requestId)
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'job_order_no')->get();

        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        // Extract IDs for frontend restriction logic
        $categoryIds = $dataItems->pluck('category_id')->unique()->values();
        $itemIds = $dataItems->pluck('item_id')->unique()->values();

        return response()->json([
            'html' => $html,
            'master' => $master,
            'allowed_categories' => $categoryIds,
            'allowed_items' => $itemIds,
            "locations_id" => $locations_id 
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

            'delivery_date' => 'required|array|min:1',
            'delivery_date.*' => 'required|date',
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
            // $PurchaseQuotation = PurchaseQuotation::findOrFail($id);
            $PurchaseQuotations = PurchaseQuotation::where("purchase_request_id", $id)
                                                    ->whereIn("am_approval_status", ["pending", "reverted", "partial approved", "approved"])
                                                    ->get();
            
            $updated_ids = [];

            foreach ($PurchaseQuotations as $PurchaseQuotation) {

                $PurchaseQuotation->update([
                    'description' => $request->description,
                    'am_change_made' => 1,
                    "am_approval_status" => "pending"
                ]);

                // IDs that came from the form (request)
                $requestRowIds = collect($request->qty ?? [])
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->toArray();

                // Save per quotation
                $updated_ids[$PurchaseQuotation->id] = $requestRowIds;

                // Update existing rows
                $purchaseQuotationData = PurchaseQuotationData::where(
                    'purchase_quotation_id',
                    $PurchaseQuotation->id
                )->whereNotIn('am_approval_status', ['approved', 'rejected'])->get();
                // dd($PurchaseQuotation->purchase_request_id);
                foreach ($purchaseQuotationData as $row) {

                    if (!in_array($row->id, $requestRowIds)) {
                        continue; // will be deleted later
                    }

                    $qty  = $request->qty[$row->id] ?? 0;
                    $rate = $request->rate[$row->id] ?? 0;

                    $row->update([
                        'qty'     => $qty,
                        'rate'    => $rate,
                        'total'   => $qty * $rate,
                        "am_approval_status" => "pending",
                        "am_change_made" => 1,
                        'remarks' => $request->remarks[$row->id] ?? null,
                        'delivery_date' => $request->delivery_date[$row->id] ?? null,
                    ]);
                }
            }


            // dd($updated_ids);

            foreach ($updated_ids as $purchaseQuotationId => $ids) {

                PurchaseQuotationData::where('purchase_quotation_id', $purchaseQuotationId)
                    ->where('am_approval_status', 'pending')
                    ->whereNotIn('id', $ids)
                    ->delete();
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
        $PurchaseQuotationData = PurchaseQuotationData::where('purchase_quotation_id', $id)
                                                        ->whereNotIn("am_approval_status", ["rejected", "approved"])
                                                        ->delete();

        return response()->json(['success' => 'Purchase quotation deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'PQ-' . $date;


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

        $purchase_quotation_no = 'PQ-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_quotation_no' => $purchase_quotation_no
            ]);
        }

        return $purchase_quotation_no;
    }

    public function getFilteredOptions(Request $request)
    {
        $supplier_id = $request->supplier_id;
        $item_id = $request->item_id;
        $uom_id = $request->uom_id;
        $status = $request->status;

        $itemsQuery = Product::select('id', 'name')->where('status', 'active');
        $suppliersQuery = Supplier::select('id', 'name')->where('status', 'active')->whereType('store_supplier');
        $uomsQuery = \App\Models\UnitOfMeasure::select('id', 'name');

        $baseDataQuery = PurchaseQuotationData::query();
        if ($status && $status != 'all') {
            $baseDataQuery->where('am_approval_status', $status);
        }

        // Apply filters to items
        if ($supplier_id && $supplier_id != 'all') {
            $itemIds = (clone $baseDataQuery)->where('supplier_id', $supplier_id)->distinct()->pluck('item_id');
            $itemsQuery->whereIn('id', $itemIds);
        }
        if ($uom_id && $uom_id != 'all') {
            $itemsQuery->where('unit_of_measure_id', $uom_id);
        }

        // Apply filters to suppliers
        if ($item_id && $item_id != 'all') {
            $supplierIds = (clone $baseDataQuery)->where('item_id', $item_id)->distinct()->pluck('supplier_id');
            $suppliersQuery->whereIn('id', $supplierIds);
        } elseif ($uom_id && $uom_id != 'all') {
            $itemIdsInUom = Product::where('unit_of_measure_id', $uom_id)->pluck('id');
            $supplierIds = (clone $baseDataQuery)->whereIn('item_id', $itemIdsInUom)->distinct()->pluck('supplier_id');
            $suppliersQuery->whereIn('id', $supplierIds);
        }

        // Apply filters to UOMs
        if ($item_id && $item_id != 'all') {
            $uomId = Product::where('id', $item_id)->value('unit_of_measure_id');
            $uomsQuery->where('id', $uomId);
        } elseif ($supplier_id && $supplier_id != 'all') {
            $itemIds = (clone $baseDataQuery)->where('supplier_id', $supplier_id)->distinct()->pluck('item_id');
            $uomIds = Product::whereIn('id', $itemIds)->distinct()->pluck('unit_of_measure_id');
            $uomsQuery->whereIn('id', $uomIds);
        }

        return response()->json([
            'items' => $itemsQuery->get(),
            'suppliers' => $suppliersQuery->get(),
            'uoms' => $uomsQuery->get()
        ]);
    }
}
