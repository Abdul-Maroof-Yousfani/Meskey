<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryChallans = ReceivingRequest::whereHas('deliveryChallan', function ($q) {
                $q->where('sauda_type', 'pohanch');
            })
            ->select('id', 'dc_no')
            ->distinct()
            ->get();
        return view('management.sales.receiving-request.index', compact('deliveryChallans'));
    }

    /**
     * Get list of receiving requests.
     */
    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $receivingRequests = ReceivingRequest::with(['deliveryChallan.customer', 'deliveryChallan.delivery_order', 'items.product'])
            ->whereHas('deliveryChallan', function ($q) {
                $q->where('sauda_type', 'pohanch');
            })
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
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`dc_no`) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(`truck_number`) LIKE ?', [$searchTerm]);
                });
            })
            ->where(function($q) {
                $q->where('am_approval_status', '!=', 'draft')
                  ->orWhereNull('am_approval_status')
                  ->orWhere(function($sq) {
                      $sq->where('am_approval_status', 'draft')
                         ->where('created_by_id', auth()->user()->id ?? null);
                  });
            })
            ->latest()
            ->paginate($perPage);

        return view('management.sales.receiving-request.getList', compact('receivingRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product', 'weighbridges'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        $labours = \App\Models\Master\Vendor::all();
        return view('management.sales.receiving-request.edit', compact('receivingRequest', 'transporters', 'labours'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        DB::beginTransaction();
        try {
            $receivingRequest = ReceivingRequest::findOrFail($id);


            if($receivingRequest->am_approval_status == "approved" || $receivingRequest->am_approval_status == 'rejected') {
                return response()->json("Receiving Request has been approved/rejected and cannot be updated.", 400);
            }

            // Calculate total labour amount
            $totalLabourAmount = 0;
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item) {
                        $bags = floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                        $rate = floatval($itemData['unloading_labour_rate'] ?? 0);
                        $totalLabourAmount += ($bags * $rate);
                    }
                }
            }

            $arrivedWeight = $request->arrived_weight ?? 0;
            $exemptedWeight = $request->exempted_weight ?? 0;
            $paymentWeight = floatval($arrivedWeight) - floatval($exemptedWeight);

            // Update main receiving request
            $receivingRequest->update([
                'labour' => $request->labour,
                'transporter' => $request->transporter,
                'labour_amount' => $receivingRequest->deliveryChallan?->labour_amount ?? $receivingRequest->labour_amount,
                'transporter_amount' => $request->transporter_amount ?? 0,
                'transporter_deduction' => $request->transporter_deduction ?? 0,
                'arrived_date' => $request->arrived_date,
                'arrived_weight' => $arrivedWeight,
                'exempted_weight' => $exemptedWeight,
                'payment_weight' => $paymentWeight,
                'unloading_paid_by' => $request->unloading_paid_by,
                'weighbridge_paid_by' => $request->weighbridge_paid_by,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            // Sync weighbridges
            $receivingRequest->weighbridges()->delete();
            $totalWeighbridgeAmount = 0;
            if ($request->has('weighbridges')) {
                foreach ($request->weighbridges as $wb) {
                    if (!empty($wb['name'])) {
                        $amt = floatval($wb['amount'] ?? 0);
                        $receivingRequest->weighbridges()->create([
                            'name' => $wb['name'],
                            'amount' => $amt
                        ]);
                        $totalWeighbridgeAmount += $amt;
                    }
                }
            }
            
            // Now update the amount in ReceivingRequest itself too
            $receivingRequest->update([
                'weighbridge_amount' => $totalWeighbridgeAmount
            ]);

            $receivingRequest->deliveryChallan()->update([
                "labour" => $request->labour,
                "transporter" => $request->transporter,
                "transporter_amount" => $request->transporter_amount ?? 0,
                "weighbridge-amount" => $totalWeighbridgeAmount
            ]);

            // Update items
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item) {
                        $item->update([
                            'unloading_labour_rate' => $itemData['unloading_labour_rate'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['data' => 'Receiving Request has been updated successfully']);
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
        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product', 'weighbridges'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        $labours = \App\Models\Master\Vendor::all();
        return view('management.sales.receiving-request.view', compact('receivingRequest', 'transporters', 'labours'));
    }
}

