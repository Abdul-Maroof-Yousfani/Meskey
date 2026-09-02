<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\Sales\LogisticsBill;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use App\Services\SalesLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogisticsBillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryChallans = LogisticsBill::select('id', 'dc_no')
            ->where('am_approval_status', 'approved')
            ->distinct()
            ->get();

        return view('management.sales.logistics-bill.index', compact('deliveryChallans'));
    }

    /**
     * Get list of approved logistics bills.
     */
    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $logisticsBills = LogisticsBill::with(['deliveryChallan.customer', 'deliveryChallan.delivery_order', 'items.product'])
            ->where('am_approval_status', 'approved')
            ->when($request->filled('dc_id_for_filter') && $request->dc_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('id', $request->dc_id_for_filter);
            })
            ->when($request->filled('dc_date_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->dc_date_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('dc_date', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            ->when($request->filled('created_at_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->created_at_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`dc_no`) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(`truck_number`) LIKE ?', [$searchTerm]);
                });
            })
            ->latest()
            ->paginate($perPage);

        return view('management.sales.logistics-bill.getList', compact('logisticsBills'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $logisticsBill = LogisticsBill::with([
            'deliveryChallan.delivery_challan_data',
            'deliveryChallan.customer',
            'deliveryChallan.delivery_order.salesOrder.logistics.items',
            'items.product',
            'items.deliveryChallanData',
            'weighbridges',
            'salesReturn.sale_return_data'
        ])->findOrFail($id);

        // Fetch any SalesReturn created for this RR / DC (from sale_return_sale_invoice pivot or sales_return_id)
        $salesReturns = \App\Models\Sales\SalesReturn::with('sale_return_data')
            ->where('am_approval_status', '!=', 'rejected')
            ->where(function($q) use ($logisticsBill) {
                $q->whereHas('receiving_requests', function($rrQ) use ($logisticsBill) {
                    $rrQ->where('receiving_requests.id', $logisticsBill->id);
                })
                ->orWhere('id', $logisticsBill->sales_return_id);
            })
            ->get();
        
        $transporters = Transporter::all();
        $labours = Vendor::all();

        return view('management.sales.logistics-bill.edit', compact('logisticsBill', 'transporters', 'labours', 'salesReturns'));
    }

    /**
     * Update the specified resource in storage and sync ledgers.
     */
    public function update(Request $request, int $id)
    {
        DB::beginTransaction();
        try {
            $logisticsBill = LogisticsBill::findOrFail($id);
            $receivingRequest = ReceivingRequest::findOrFail($id);

            // Calculate total unloading labour amount
            $totalLabourAmount = 0;
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item && $item->receiving_request_id == $logisticsBill->id) {
                        $bags = floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                        $rate = floatval($itemData['unloading_labour_rate'] ?? 0);
                        $totalLabourAmount += ($bags * $rate);

                        $item->update([
                            'unloading_labour_rate' => $rate,
                        ]);
                    }
                }
            }

            $exemptedWeight = floatval($request->exempted_weight ?? 0);
            $paymentWeight = floatval($logisticsBill->arrived_weight ?? 0) - $exemptedWeight;

            $salesReturnId = $request->filled('sales_return_id') ? $request->sales_return_id : null;
            $salesReturnQty = floatval($request->sales_return_qty ?? 0);
            $salesReturnTransporterAmount = floatval($request->sales_return_transporter_amount ?? 0);
            $transporterOtherAmount = floatval($request->transporter_other_amount ?? 0);
            $demurrageDetentionAmount = floatval($request->demurrage_detention_amount ?? 0);

            // If salesReturnId is selected and salesReturnQty is 0, auto-fill it from the SalesReturn model
            if ($salesReturnId && $salesReturnQty <= 0) {
                $sr = \App\Models\Sales\SalesReturn::with('sale_return_data')->find($salesReturnId);
                $salesReturnQty = floatval($sr?->sale_return_data->sum('quantity') ?? 0);
            }

            // Update main receiving request / logistics bill record
            $logisticsBill->update([
                'exempted_weight' => $exemptedWeight,
                'payment_weight' => $paymentWeight,
                'transporter_deduction' => $request->transporter_deduction ?? 0,
                'transporter_other_amount' => $transporterOtherAmount,
                'demurrage_detention_amount' => $demurrageDetentionAmount,
                'sales_return_id' => $salesReturnId,
                'sales_return_qty' => $salesReturnQty,
                'sales_return_transporter_amount' => $salesReturnTransporterAmount,
                'unloading_paid_by' => $request->unloading_paid_by,
                'weighbridge_paid_by' => $request->weighbridge_paid_by,
                'labour_amount' => $totalLabourAmount,
            ]);


            // Re-sync ledgers via SalesLedgerService
            $receivingRequest->refresh();
            app(SalesLedgerService::class)->handleReceivingRequestApproval($receivingRequest);

            DB::commit();
            return response()->json(['data' => 'Logistics Bill has been updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function view(int $id)
    {
        $logisticsBill = LogisticsBill::with([
            'deliveryChallan.delivery_challan_data',
            'deliveryChallan.customer',
            'deliveryChallan.delivery_order.salesOrder.logistics.items',
            'items.product',
            'items.deliveryChallanData',
            'weighbridges',
            'salesReturn.sale_return_data'
        ])->findOrFail($id);
        
        $transporters = Transporter::all();
        $labours = Vendor::all();

        return view('management.sales.logistics-bill.view', compact('logisticsBill', 'transporters', 'labours'));
    }
}
