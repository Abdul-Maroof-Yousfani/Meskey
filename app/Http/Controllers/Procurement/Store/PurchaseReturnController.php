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

        return view('management.procurement.store.purchase-return.getItem', ['purchase_bills' => $filtered_bills]);
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
                    'bill_date' => $bill->bill_date
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
            // Validate date is not before any of the selected purchase bills' dates
            $purchaseBills = PurchaseBill::whereIn('id', $request->purchase_bill_ids)->get();
            foreach ($purchaseBills as $bill) {
                if (strtotime($bill->bill_date) > strtotime($request->date)) {
                    return response()->json("Return date cannot be before purchase bill date: " . $bill->bill_date . " for bill " . $bill->bill_no, 422);
                }
            }

            $purchaseReturn = PurchaseReturn::create([
                'pr_no' => $request->pr_no,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'supplier_id' => $request->supplier_id,
                'company_location_id' => $request->company_location_id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
                'am_change_made' => 1,
            ]);

            // Attach the selected purchase bills
            $purchaseReturn->purchaseBills()->attach($request->purchase_bill_ids);

            foreach ($request->item_id as $index => $item_id) {
                $balance = getAvailableReturnBalance($request->bill_data_id[$index]);

                if ($request->quantity[$index] > $balance) {
                    return response()->json("Available balance is $balance. You cannot exceed this balance", 422);
                }

                // Get discount information from purchase bill data
                $billData = PurchaseBillData::find($request->bill_data_id[$index]);

                $purchaseReturn->purchase_return_data()->create([
                    'purchase_bill_data_id' => $request->bill_data_id[$index],
                    'item_id' => $item_id,
                    'quantity' => $request->quantity[$index],
                    'rate' => $request->rate[$index],
                    'gross_amount' => $request->gross_amount[$index],
                    'tax_percent' => $request->tax_percent[$index] ?? 0,
                    'tax_amount' => $request->tax_amount[$index] ?? 0,
                    'discount_percent' => $billData->discount_percent ?? 0,
                    'discount_amount' => $billData->discount_amount ?? 0,
                    'net_amount' => $request->net_amount[$index],
                    'description' => $request->description[$index],
                ]);
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
        DB::beginTransaction();
        $purchaseReturn = PurchaseReturn::find($id);
        try {
            // Validate date is not before any of the selected purchase bills' dates
            $purchaseBills = PurchaseBill::whereIn('id', $request->purchase_bill_ids)->get();
            foreach ($purchaseBills as $bill) {
                if (strtotime($bill->bill_date) > strtotime($request->date)) {
                    return response()->json("Return date cannot be before purchase bill date: " . $bill->bill_date . " for bill " . $bill->bill_no, 422);
                }
            }

            $purchaseReturn->update([
                'pr_no' => $request->pr_no,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'supplier_id' => $request->supplier_id,
                'company_location_id' => $request->company_location_id,
                'remarks' => $request->remarks,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
            ]);

            // Sync the selected purchase bills
            $purchaseReturn->purchaseBills()->sync($request->purchase_bill_ids);

            $purchaseReturn->purchase_return_data()->delete();

            foreach ($request->item_id as $index => $item_id) {
                $balance = getAvailableReturnBalance($request->bill_data_id[$index], $id);

                if ($request->quantity[$index] > $balance) {
                    return response()->json("Available balance is $balance. You cannot exceed this balance", 422);
                }

                // Get discount information from purchase bill data
                $billData = PurchaseBillData::find($request->bill_data_id[$index]);
                $purchaseReturn->purchase_return_data()->create([
                    'purchase_bill_data_id' => $request->bill_data_id[$index],
                    'item_id' => $item_id,
                    'quantity' => $request->quantity[$index],
                    'rate' => $request->rate[$index],
                    'gross_amount' => $request->gross_amount[$index],
                    'tax_percent' => $request->tax_percent[$index] ?? 0,
                    'tax_amount' => $request->tax_amount[$index] ?? 0,
                    'discount_percent' => $billData->discount_percent ?? 0,
                    'discount_amount' => $billData->discount_amount ?? 0,
                    'net_amount' => $request->net_amount[$index],
                    'description' => $request->description[$index],
                ]);
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
        $purchaseReturn->purchase_return_data()->delete();
        $purchaseReturn->delete();

        return response()->json("Purchase Return has been deleted!");
    }
}
