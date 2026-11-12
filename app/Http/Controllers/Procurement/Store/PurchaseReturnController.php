<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseReturnRequest;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\Store\PurchaseBillData;
use App\Models\Procurement\Store\PurchaseReturn;
use App\Models\Procurement\Store\PurchaseReturnData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\StoreLedgerService;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase-return.index');
    }

    public function create()
    {
        $suppliers = \App\Models\Master\Supplier::all();
        $purchaseBills = PurchaseBill::where('am_approval_status', 'approved')->with('supplier')->get();

        return view('management.procurement.store.purchase-return.create', compact('suppliers', 'purchaseBills'));
    }

    public function view(int $id)
    {
        $purchaseReturn = PurchaseReturn::with('purchase_return_data.purchase_bill_data.item', 'purchaseBills')->find($id);

        return view('management.procurement.store.purchase-return.view', compact('purchaseReturn'));
    }

    public function edit(int $id)
    {
        $purchaseReturn = PurchaseReturn::with('purchaseBills')->find($id);
        $approvedPurchaseBills = PurchaseBill::where('am_approval_status', 'approved')
            ->with('supplier', 'bill_data')
            ->get();

        return view('management.procurement.store.purchase-return.edit', compact('approvedPurchaseBills', 'purchaseReturn'));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $PurchaseReturns = PurchaseReturn::with([
                'purchaseBills.supplier',
                'supplier',
                'company_location',
                'created_by_user',
                'purchase_return_data.item',
                'purchase_return_data.purchase_bill_data.purchaseBill.supplier'
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`pr_no`) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm])
                        ->orWhereHas('supplier', function ($query) use ($searchTerm) {
                            $query->whereRaw('LOWER(`name`) LIKE ?', [$searchTerm]);
                        })
                        ->orWhereHas('purchaseBills', function ($query) use ($searchTerm) {
                            $query->whereRaw('LOWER(`bill_no`) LIKE ?', [$searchTerm]);
                        });
                });
            })
            ->latest()
            ->paginate($perPage);
        $purchaseReturns = $PurchaseReturns;

        return view('management.procurement.store.purchase-return.getList', compact('PurchaseReturns', "purchaseReturns"));
    }

    public function getItems(Request $request)
    {
        $purchase_bill_ids = $request->purchase_bill_ids;

        $purchase_bills = PurchaseBill::with('bill_data.item')
            ->whereIn('id', $purchase_bill_ids)
            ->get();

        // Filter items to only show those with available balance for return
        $filtered_bills = $purchase_bills->map(function ($bill) {
            $bill->bill_data = $bill->bill_data->filter(function ($billData) {
                return getAvailableReturnBalance($billData->id) > 0;
            });
            return $bill;
        })->filter(function ($bill) {
            return $bill->bill_data->isNotEmpty();
        });

        $categories = \App\Models\Category::select('id', 'name')->where('category_type', 'general_items')->get();
        return view('management.procurement.store.purchase-return.getItem', [
            'purchase_bills' => $filtered_bills,
            'categories' => $categories
        ]);
    }


    public function getPurchaseBillsBySupplier(Request $request)
    {
        $supplier_id = $request->supplier_id;

        $purchase_bills = PurchaseBill::where('supplier_id', $supplier_id)
            ->where('am_approval_status', 'approved')
            ->with('bill_data')
            ->get()
            ->filter(function ($bill) {
                // Check if bill has any items with available balance for return
                return $bill->bill_data->contains(function ($billData) {
                    return getAvailableReturnBalance($billData->id) > 0;
                });
            })
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'text' => $bill->bill_no . ' - ' . ($bill->supplier->name ?? ''),
                    'bill_date' => $bill->bill_date,
                    'location_id' => $bill->location_id
                ];
            })
            ->values();

        return response()->json($purchase_bills);
    }

    public function getNumber(Request $request)
    {
        $date = Carbon::parse($request->return_date ?? $request->date)->format('Y-m-d');

        $prefix = 'PR-' . $date;

        $latestReturn = PurchaseReturn::where('pr_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        if ($latestReturn) {
            $parts = explode('-', $latestReturn->pr_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $pr_no = 'PR-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'pr_no' => $pr_no,
        ]);
    }

    public function store(PurchaseReturnRequest $request)
    {
        DB::beginTransaction();
        try {
            if ($error = $this->validateReturnDate($request)) {
                DB::rollBack();
                return response()->json($error, 422);
            }

            $purchaseReturn = PurchaseReturn::create([
                'pr_no' => $request->pr_no,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'supplier_id' => $request->supplier_id,
                'company_location_id' => is_array($request->company_location_id) ? implode(',', $request->company_location_id) : $request->company_location_id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
                'am_change_made' => 1,
            ]);

            // Attach the selected purchase bills
            $purchaseReturn->purchaseBills()->attach($request->purchase_bill_ids);

            if ($error = $this->saveReturnLines($purchaseReturn, $request)) {
                DB::rollBack();
                return response()->json($error, 422);
            }

            DB::commit();
            return response()->json("Purchase Return has been created");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function update(PurchaseReturnRequest $request, int $id)
    {
        $purchaseReturn = PurchaseReturn::findOrFail($id);

        if($purchaseReturn->am_approval_status == "approved" || $purchaseReturn->am_approval_status == "rejected") {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return is already approved or rejected.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($error = $this->validateReturnDate($request)) {
                DB::rollBack();
                return response()->json($error, 422);
            }

            // Entries an older version of this module posted on save (before pr_no can change).
            // Stock and ledger now post on approval only.
            $ledger = app(StoreLedgerService::class);
            $ledger->clearLegacyReturnEntries($purchaseReturn);
            $ledger->clearPurchaseReturnStock($purchaseReturn);

            $purchaseReturn->update([
                'pr_no' => $request->pr_no,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'supplier_id' => $request->supplier_id,
                'company_location_id' => is_array($request->company_location_id) ? implode(',', $request->company_location_id) : $request->company_location_id,
                'remarks' => $request->remarks,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
            ]);

            // Sync the selected purchase bills
            $purchaseReturn->purchaseBills()->sync($request->purchase_bill_ids);

            $purchaseReturn->purchase_return_data()->delete();

            if ($error = $this->saveReturnLines($purchaseReturn, $request, $id)) {
                DB::rollBack();
                return response()->json($error, 422);
            }

            DB::commit();
            return response()->json("Purchase Return has been updated");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        if($purchaseReturn->am_approval_status == "approved" || $purchaseReturn->am_approval_status == "rejected") {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return is already approved or rejected.',
            ], 422);
        }

        DB::transaction(function () use ($purchaseReturn) {
            $ledger = app(StoreLedgerService::class);
            $ledger->clearLegacyReturnEntries($purchaseReturn);
            $ledger->clearPurchaseReturnStock($purchaseReturn);
            $purchaseReturn->purchase_return_data()->delete();
            $purchaseReturn->delete();
        });

        return response()->json("Purchase Return has been deleted!");
    }

    private function validateReturnDate(Request $request): ?string
    {
        $purchaseBills = PurchaseBill::whereIn('id', $request->purchase_bill_ids ?? [])->get();
        foreach ($purchaseBills as $bill) {
            if (strtotime($bill->bill_date) > strtotime($request->date)) {
                return "Return date cannot be before purchase bill date: " . $bill->bill_date . " for bill " . $bill->bill_no;
            }
        }

        return null;
    }

    /**
     * Creates the return lines. Qty is checked against the bill balance; discount and tax %
     * come from the bill line and every amount is recalculated here (not taken from the browser).
     */
    private function saveReturnLines(PurchaseReturn $purchaseReturn, Request $request, ?int $excludeReturnId = null): ?string
    {
        foreach ($request->item_id as $index => $item_id) {
            $billData = PurchaseBillData::find($request->bill_data_id[$index] ?? null);
            if (!$billData) {
                return "Bill line not found for row " . ($index + 1) . ".";
            }
            if ($billData->purchaseBill?->am_approval_status !== 'approved') {
                return "Purchase return can only be made against an approved purchase bill.";
            }

            $quantity = (float) ($request->quantity[$index] ?? 0);
            $balance = getAvailableReturnBalance($billData->id, $excludeReturnId);
            if ($quantity <= 0 || $quantity > $balance) {
                return "Available balance is $balance. You cannot exceed this balance";
            }

            // rate, discount % and tax % are read-only on the form and always come from the bill line
            $rate = (float) $billData->rate;
            $amounts = StoreLedgerService::calculateReturnLine(
                $quantity,
                $rate,
                (float) ($billData->discount_percent ?? 0),
                (float) ($billData->tax_percent ?? 0),
                (float) ($request->deduction_per_piece[$index] ?? 0)
            );

            $purchaseReturn->purchase_return_data()->create([
                'purchase_bill_data_id' => $billData->id,
                'item_id' => $billData->item_id,
                'quantity' => $quantity,
                'rate' => $rate,
                'description' => $request->description[$index] ?? null,
            ] + $amounts);
        }

        return null;
    }
}
