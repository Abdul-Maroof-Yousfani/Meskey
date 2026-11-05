<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RejectionReturnController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase-return.rejection_index');
    }

    public function getList(Request $request)
    {
        $PurchaseReturns = \App\Models\Procurement\Store\RejectionReturn::with(['supplier', 'items.item', 'grn'])
            ->latest()
            ->paginate($request->limit ?? 10);

        $GroupedRejectionReturns = $PurchaseReturns->map(function($return) {
            $items = $return->items->map(function($data) {
                return [
                    'name' => $data->item->name ?? 'N/A',
                    'rejected_qty' => $data->quantity,
                    'rate' => $data->rate ?? 0,
                    'uom' => $data->item->unitOfMeasure->name ?? ''
                ];
            })->toArray();

            return [
                'id' => $return->id,
                'request_no' => $return->return_no,
                'grn_no' => $return->grn->purchase_order_receiving_no ?? 'N/A',
                'truck_no' => $return->truck_no,
                'supplier' => $return->supplier->name ?? 'N/A',
                'date' => $return->date,
                'status' => $return->am_approval_status ?? 'approved',
                'request_rowspan' => count($items) ?: 1,
                'items' => $items
            ];
        });
        
        return view('management.procurement.store.purchase-return.rejection_getList', compact('GroupedRejectionReturns', 'PurchaseReturns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grn_id' => 'required|exists:purchase_order_receivings,id',
            'purchase_date' => 'required|date',
            'item_id' => 'required|array',
            'qty' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $grn = \App\Models\Procurement\Store\PurchaseOrderReceiving::find($request->grn_id);

            if ($error = $this->validateReturnQty($request, (int) $request->grn_id)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $error], 422);
            }

            // Generate Return No
            $returnNo = generateUniqueNumber('rejection_returns', 'RET-', null, 'return_no');

            $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::create([
                'return_no' => $returnNo,
                'date' => $request->purchase_date,
                'reference_no' => $request->reference_no,
                'truck_no' => $request->truck_no,
                'grn_id' => $request->grn_id,
                'supplier_id' => $grn->supplier_id,
                'company_id' => auth()->user()->current_company_id,
                'created_by' => auth()->user()->id,
                'am_approval_status' => 'approved',
                'remarks' => $request->remarks,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                if (isset($request->qty[$index]) && $request->qty[$index] > 0) {
                    $rejectionReturn->items()->create([
                        'item_id' => $itemId,
                        'rate' => $request->rate[$index] ?? 0,
                        'quantity' => $request->qty[$index],
                        'weight' => $request->weight[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Rejection Return created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        // Only approved QCs post the stock-in, so only those bags can be returned.
        $approvedGRNs = PurchaseOrderReceiving::whereHas('purchaseOrderReceivingData.qc', function($q) {
                $q->where('rejection_return', '>', 0)
                  ->where('am_approval_status', 'approved');
            })
            ->where(function($query) {
                // Either no rejection return yet
                $query->whereDoesntHave('rejectionReturns')
                // Or all existing rejection returns for this GRN are 'rejected'
                ->orWhereDoesntHave('rejectionReturns', function($q) {
                    $q->where('am_approval_status', '!=', 'rejected');
                });
            })
            ->get();

        return view('management.procurement.store.purchase-return.rejection_create', compact('approvedGRNs'));
    }

    public function edit($id)
    {
        $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::with(['items.item', 'grn.supplier'])->findOrFail($id);
        
        $approvedGRNs = PurchaseOrderReceiving::whereHas('purchaseOrderReceivingData.qc', function($q) {
                  $q->where('rejection_return', '>', 0)
                    ->where('am_approval_status', 'approved');
            })
            ->get();
            
        return view('management.procurement.store.purchase-return.rejection_edit', compact('rejectionReturn', 'approvedGRNs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date' => 'required|date',
            'item_id' => 'required|array',
            'qty' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::findOrFail($id);

            if ($error = $this->validateReturnQty($request, (int) $rejectionReturn->grn_id, (int) $rejectionReturn->id)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $error], 422);
            }

            $rejectionReturn->update([
                'date' => $request->purchase_date,
                'truck_no' => $request->truck_no,
                'remarks' => $request->remarks,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            // Item sync: remove old items with their stock-out/ledger rows, then re-add
            $rejectionReturn->clearItems();
            foreach ($request->item_id as $index => $itemId) {
                if (isset($request->qty[$index]) && $request->qty[$index] > 0) {
                    $rejectionReturn->items()->create([
                        'item_id' => $itemId,
                        'rate' => $request->rate[$index] ?? 0,
                        'quantity' => $request->qty[$index],
                        'weight' => $request->weight[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Rejection Return updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function view($id)
    {
        $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::with(['items.item', 'grn.supplier', 'creator'])->findOrFail($id);
        return view('management.procurement.store.purchase-return.rejection_view', compact('rejectionReturn'));
    }

    public function gateOut($id)
    {
        $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::with(['items.item', 'grn.supplier', 'creator'])->findOrFail($id);
        return view('management.procurement.store.purchase-return.rejection_gate_out', compact('rejectionReturn'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $rejectionReturn = \App\Models\Procurement\Store\RejectionReturn::findOrFail($id);
            // Removes the stock-out too, so the bags are back in stock.
            $rejectionReturn->clearItems();
            $rejectionReturn->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Rejection Return deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Per item, the qty returned must not exceed the approved QC's "rejected without deduction"
     * qty for this GRN, minus what other active rejection returns already took.
     */
    private function validateReturnQty(Request $request, int $grnId, ?int $excludeReturnId = null): ?string
    {
        $requested = [];
        foreach ($request->item_id ?? [] as $index => $itemId) {
            $qty = (float) ($request->qty[$index] ?? 0);
            if ($qty > 0) {
                $requested[$itemId] = ($requested[$itemId] ?? 0) + $qty;
            }
        }

        if (empty($requested)) {
            return 'Please add at least one item with quantity.';
        }

        foreach ($requested as $itemId => $qty) {
            $allowed = \App\Models\Procurement\Store\RejectionReturn::returnableQty($grnId, (int) $itemId, $excludeReturnId);
            if ($qty > $allowed + 0.0001) {
                $name = \App\Models\Product::find($itemId)->name ?? "Item {$itemId}";
                return "Return qty for '{$name}' ({$qty}) exceeds the approved QC rejected-without-deduction balance ({$allowed}).";
            }
        }

        return null;
    }
}
