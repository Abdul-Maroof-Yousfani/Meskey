<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\DebitNoteRequest;
use App\Models\Procurement\Store\DebitNote;
use App\Models\Procurement\Store\DebitNoteData;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\Store\PurchaseBillData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{

    public function index() {
        

        return view("management.procurement.store.debit-note.index");
    }
    public function create() {
        $grns = PurchaseOrderReceiving::select("id", "purchase_order_receiving_no")
                                        ->get();
        
        $grns = $grns->filter(function($grn) {
            $purhase_bills = $grn->bills;
            foreach($purhase_bills as $purhase_bill) {
                foreach($purhase_bill->bill_data as $bill_data) {
                    $remainingQty = purchaseBillDistribution($bill_data->id);
                    if($remainingQty > 0) {
                        return true;
                    }
                }
            }
            return false;
        });
       
        return view("management.procurement.store.debit-note.create", compact("grns"));
    }
    public function store(DebitNoteRequest $request) {
        $debitNote = DebitNote::create([
            'grn_id' => $request->grn_id,
            'bill_id' => $request->bill_id,
            'reference_number' => $request->reference_number,
            'transaction_date' => $request->transaction_date,
            'created_by' => auth()->user()->id,
        ]);

        // Create debit note items
        if ($request->has('item_id')) {
            foreach ($request->item_id as $key => $itemId) {
                DebitNoteData::create([
                    'debit_note_id' => $debitNote->id,
                    'grn_id' => $request->grn_id,
                    'bill_id' => $request->bill_id,
                    'purchase_bill_data_id' => $request->bill_data_id[$key],
                    'item_id' => $itemId,
                    'grn_qty' => $request->grn_qty[$key],
                    'debit_note_quantity' => $request->debit_note_quantity[$key],
                    'rate' => $request->rate[$key],
                    'amount' => $request->amount[$key],
                    'am_approval_status' => 'pending',
                    'am_change_made' => 0,
                    
                ]);
            }
        }

        return response()->json([
            'success' => "Debit Note has been created successfully!",
            'message' => 'Debit Note created successfully',
            'redirect' => route('store.debit-note.index')
        ]);
    }

    public function update(DebitNoteRequest $request, $id) {
        $debitNote = DebitNote::findOrFail($id);

        // Update debit note main data
        $debitNote->update([
            'grn_id' => $request->grn_id,
            'bill_id' => $request->bill_id,
            'reference_number' => $request->reference_number,
            'transaction_date' => $request->transaction_date,
            "am_approval_status" => "pending",
            "am_change_made" => 1
        ]);

        // Delete existing debit note data items
        $debitNote->debit_note_data()->delete();

        // Create new debit note items
        if ($request->has('item_id')) {
            foreach ($request->item_id as $key => $itemId) {
                DebitNoteData::create([
                    'debit_note_id' => $debitNote->id,
                    'grn_id' => $request->grn_id,
                    'bill_id' => $request->bill_id,
                    'purchase_bill_data_id' => $request->bill_data_id[$key],
                    'item_id' => $itemId,
                    'grn_qty' => $request->grn_qty[$key],
                    'debit_note_quantity' => $request->debit_note_quantity[$key],
                    'rate' => $request->rate[$key],
                    'amount' => $request->amount[$key],
                    'am_approval_status' => 'pending',
                    'am_change_made' => 0,
                ]);
            }
        }

        return response()->json([
            'success' => "Debit Note has been updated successfully!",
            'message' => 'Debit Note updated successfully',
            'redirect' => route('store.debit-note.index')
        ]);
    }

    public function show($id) {
        $debitNote = DebitNote::with(['debit_note_data.item', 'grn', 'bill'])->findOrFail($id);
        return view('management.procurement.store.debit-note.show', compact('debitNote'));
    }

    public function edit($id) {
        $debitNote = DebitNote::with(['debit_note_data.item', 'grn', 'bill'])->findOrFail($id);

        // Get GRNs that have bills
        $grns = PurchaseOrderReceiving::select("id", "purchase_order_receiving_no")
                                        ->whereHas("bills")
                                        ->get();

        

        // Get bills for the current GRN
        $bills = PurchaseBill::select("id", "bill_no")
                            ->where("purchase_order_receiving_id", $debitNote->grn_id)
                            ->where("am_approval_status", "approved")
                            ->get();

        // Calculate remaining quantities for each item in the debit note using helper function
        foreach ($debitNote->debit_note_data as $debitNoteItem) {
            // Use helper function to get remaining balance, excluding current debit note
            $remainingQty = getDebitNoteBalance($debitNoteItem->purchase_bill_data_id, $debitNote->id);

            $debitNoteItem->remaining_qty = $remainingQty;

            // Get original quantity for reference
            $billData = PurchaseBillData::find($debitNoteItem->purchase_bill_data_id);
            $debitNoteItem->original_qty = $billData ? $billData->qty : 0;
        }

        return view("management.procurement.store.debit-note.edit", compact("debitNote", "grns", "bills"));
    }

    public function getList() {
        $debit_notes = DebitNote::with([
            "debit_note_data.item",
            "grn",
            "bill"
        ])->paginate(25);

        return view("management.procurement.store.debit-note.getList", compact("debit_notes"));
    }

    public function get_bills($grn_id) {
        // Get approved bills for the GRN that have at least one item with remaining quantity > 0

        $data = [];


        $purchase_bills = PurchaseBill::where('am_approval_status', 'approved')
                        ->where('purchase_order_receiving_id', $grn_id)
                        ->get()
                        ->filter(fn($purchase_bill) =>
                            collect($purchase_bill->bill_data)->contains(fn($bill_data) =>
                                getDebitNoteBalance($bill_data['id'] ?? $bill_data->id) > 0
                            )
                        )
                        ->values()
                        ->toArray();
    
        // return response()->json(array_values($purchase_bill->bill_data));

        return response()->json($purchase_bills);
    }

    public function get_bill_items($bill_id) {
        $bill = PurchaseBill::with(['bill_data.item'])->find($bill_id);

        if (!$bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        // Calculate remaining quantity for each item using helper function
        $items = $bill->bill_data->map(function ($item) {
            // Use helper function to get remaining balance
            $remainingQty = purchaseBillDistribution($item->id);

            // Add remaining quantity to the item
            $item->remaining_qty = $remainingQty;
            return $item;
        });



        return response()->json([
            'bill' => $bill,
            'items' => $items
        ]);
    }

    public function get_number(Request $request) {
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'DN-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = DebitNote::where('reference_number', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->reference_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $debit_note_no = 'DN-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // if (! $locationId && ! $contractDate) {
        //     return response()->json([
        //         'success' => true,
        //         'purchase_request_no' => $purchase_request_no,
        //     ]);
        // }

        return $debit_note_no;
    }

    public function destroy(DebitNote $debit_note) {
        $debit_note->debit_note_data()->delete();
        $debit_note->delete();
        return response()->json("Debit Note has been deleted!");
    }
}
