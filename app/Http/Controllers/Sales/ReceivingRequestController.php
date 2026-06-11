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
        $deliveryChallans = ReceivingRequest::select('id', 'dc_no')->distinct()->get();
        return view('management.sales.receiving-request.index', compact('deliveryChallans'));
    }

    /**
     * Get list of receiving requests.
     */
    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $receivingRequests = ReceivingRequest::with(['deliveryChallan', 'items.product'])
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
            ->latest()
            ->paginate($perPage);

        return view('management.sales.receiving-request.getList', compact('receivingRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        return view('management.sales.receiving-request.edit', compact('receivingRequest', 'transporters'));
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

            // Update main receiving request
            $receivingRequest->update([
                'labour' => $request->labour,
                'transporter' => $request->transporter,
                'inhouse_weighbridge' => $request->inhouse_weighbridge,
                'labour_amount' => $request->labour_amount ?? 0,
                'transporter_amount' => $request->transporter_amount ?? 0,
                'weighbridge_amount' => $request->inhouse_weighbridge_amount ?? 0,
                'inhouse_weighbridge_amount' => $request->weighbridge_amount ?? 0,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            $receivingRequest->deliveryChallan()->update([
                "labour" => $request->labour,
                "labour_amount" => $request->labour_amount ?? 0,
                "transporter" => $request->transporter,
                "transporter_amount" => $request->transporter_amount ?? 0,
                "inhouse-weighbridge" => $request->inhouse_weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount ?? 0
            ]);

            // Update items
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item) {
                        $receivingWeight = floatval($itemData['receiving_weight'] ?? 0);
                        $sellerPortion = floatval($itemData['seller_portion'] ?? 0);
                        $dispatchWeight = floatval($item->dispatch_weight);
                        
                        $differenceWeight = $dispatchWeight - $receivingWeight;
                        $remainingAmount = $differenceWeight - $sellerPortion;

                        $item->update([
                            'receiving_weight' => $receivingWeight,
                            'seller_portion' => $sellerPortion,
                            'difference_weight' => $differenceWeight,
                            'remaining_amount' => $remainingAmount,
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
        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        return view('management.sales.receiving-request.view', compact('receivingRequest', 'transporters'));
    }
}

