<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\DirectReceiptVoucherRequest;
use App\Http\Requests\Finance\ReceiptVoucherRequest;
use App\Models\Master\Account\Account;
use App\Models\Master\Customer;
use App\Models\Master\Tax;
use App\Models\ReceiptVoucher;
use App\Models\ReceiptVoucherItem;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Master\Account\Transaction;
use App\Models\ReceiptVoucherBankDetail;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptVoucherController extends Controller
{
    public function index()
    {
        return view('management.finance.receipt_voucher.index');
    }

    public function getitems(Request $request)
    {
        $items = json_decode($request->items);
        $taxes = Tax::where("status", "active")->get();
        $exclude_rv_id = $request->exclude_rv_id ?? null;

        return view("management.finance.receipt_voucher.getItems", compact("items", "taxes", "exclude_rv_id"));
    }


    public function direct_receipt_voucher(DirectReceiptVoucherRequest $request)
    {
        DB::beginTransaction();
        try {
            $receipt_voucher = ReceiptVoucher::create([
                ...$request->validated(),
                "is_direct" => 1
            ]);
            $amount = 0;


            foreach ($request->account as $index => $account) {

                $receipt_voucher->items()->create([
                    "reference_id" => "1234",
                    "reference_type" => "direct",
                    "amount" => $request->amount[$index],
                    "tax_id" => $request->tax_id[$index],
                    "tax_amount" => $request->tax_amount[$index],

                    "net_amount" => $request->net_amount[$index],
                    "line_desc" => $request->description[$index],
                    'account_id' => $request->account[$index]
                ]);
                createTransaction(
                    $request->net_amount[$index],
                    $request->account[$index],
                    4,
                    $receipt_voucher->unique_no,
                    'credit',
                    'no',
                    [
                        'counter_account_id' => $request->account_id,
                    ]
                );

                $amount += $request->net_amount[$index];
            }

            createTransaction(
                $amount,
                $request->account_id,
                4,
                $receipt_voucher->unique_no,
                'debit',
                'no',

            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getDocumentsForRv(Request $request)
    {
        $is_advance = $request->is_advance === 'true' ? true : false;
        $customer_id = $request->customer_id;
        $exclude_rv_id = $request->exclude_rv_id ?? null;
        $dropdowndData = [];
        $data = [];

        if ($is_advance) {
            $dropdowndData[] = [
                "id" => "",
                "text" => "Select Sale Order"
            ];

            $data = SalesOrder::select("id", "reference_no")
                ->with("sales_order_data")
                ->where("customer_id", $customer_id)
                ->where("am_approval_status", 'approved')
                ->get()
                ->filter(function ($saleOrder) use ($exclude_rv_id) {
                    // Example: keep only if any related sale_order_data has quantity > 0
                    return $saleOrder->sales_order_data->contains(function ($item) use ($saleOrder, $exclude_rv_id) {
                        $balance = receipt_voucher_balance($item->sale_order_id, "sale_order", $exclude_rv_id);
                        return $balance > 0;
                    });
                });


        } else {
            $dropdowndData[] = [
                "id" => "",
                "text" => "Select Sale Invoice"
            ];

            $data = SalesInvoice::select("id", "si_no as reference_no")
                ->where("customer_id", $customer_id)
                ->where("am_approval_status", "approved")
                ->get()
                ->filter(function ($sale_invoice) use ($exclude_rv_id) {
                    // Example: keep only if any related sale_order_data has quantity > 0
                    return $sale_invoice->sales_invoice_data->contains(function ($item) use ($sale_invoice, $exclude_rv_id) {
                        $balance = receipt_voucher_balance($item->sales_invoice_id, "sales_invoice", $exclude_rv_id);
                        return $balance > 0;
                    });
                });
        }



        foreach ($data as $datum) {
            $dropdowndData[] = [
                "id" => $datum->id,
                "text" => $datum->reference_no
            ];

        }


        return $dropdowndData;


    }

    public function getList(Request $request)
    {
        $receiptVouchers = ReceiptVoucher::with(['account', 'customer', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('unique_no', 'like', $searchTerm)
                        ->orWhere('ref_bill_no', 'like', $searchTerm)
                        ->orWhere('cheque_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25))
            ->through(function ($item) {
                $item->is_advance = $item->items->where("reference_type", "sale_order")->isNotEmpty();
                return $item;
            });



        return view('management.finance.receipt_voucher.getList', compact('receiptVouchers'));
    }

    public function create()
    {
        $customers = Customer::select('id', 'name')->get();

        $saleOrders = SalesOrder::with('customer')
            ->where('am_approval_status', 'approved')
            ->latest()
            ->get(['id', 'reference_no', 'order_date', 'customer_id']);

        $salesInvoices = SalesInvoice::with(['customer', 'delivery_challans.receivingRequest'])
            ->where('am_approval_status', 'approved')
            ->whereHas('delivery_challans.receivingRequest')
            ->latest()
            ->get(['id', 'si_no', 'invoice_date', 'customer_id']);

        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();

        $accounts = Account::whereHas('parent', function ($q) {
            $q->whereIn('hierarchy_path', ['1-1', '1-4']);
        })->get();

        return view('management.finance.receipt_voucher.create', compact('customers', 'saleOrders', 'salesInvoices', 'taxes', 'accounts'));
    }

        public function edit($id)
    {
        $receiptVoucher = ReceiptVoucher::with(['items', 'advances', 'account', 'customer', 'bankDetails'])->findOrFail($id);

        $customers = Customer::select('id', 'name')->get();
        $accounts = Account::whereHas('parent', function ($q) {
            $q->whereIn('hierarchy_path', ['1-1', '1-4']);
        })->get();
        $saleOrders = SalesOrder::with('customer')
            ->where('am_approval_status', 'approved')
            ->latest()
            ->get(['id', 'reference_no', 'order_date', 'customer_id']);

        $salesInvoices = SalesInvoice::with(['customer', 'delivery_challans.receivingRequest'])
            ->where('am_approval_status', 'approved')
            ->whereHas('delivery_challans.receivingRequest')
            ->latest()
            ->get(['id', 'si_no', 'invoice_date', 'customer_id', 'reference_number']);

        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();

        $selectedReferences = [];
        $initialItems = $receiptVoucher->items->filter(function ($item) {
            return $item->reference_type !== 'not-allocated';
        })->map(function ($item) use (&$selectedReferences) {
            $selectedReferences[] = (string) $item->reference_id;
            $docNo = '';
            $customerName = '';
            $date = now()->format('Y-m-d');
            $amountFromSource = $item->amount;
            $quantityFromSource = 0;

            if ($item->reference_type === 'sale_order') {
                $so = SalesOrder::with(['customer', 'sales_order_data'])->find($item->reference_id);
                if ($so) {
                    $docNo = SalesOrder::find($item->reference_id)->reference_no;
                    $customerName = $so->customer->name ?? '';
                    $date = $so->order_date ? \Carbon\Carbon::parse($so->order_date)->format('Y-m-d') : optional($so->created_at)->format('Y-m-d');
                    $quantityFromSource = $so->sales_order_data->sum(function ($row) {
                        return (float) ($row->qty ?? 0);
                    });
                }
            } else {
                $inv = SalesInvoice::with(['customer', 'sales_invoice_data'])->find($item->reference_id);
                if ($inv) {
                    $docNo = $inv->si_no ?? ('INV-' . $inv->id);
                    if ($inv->reference_number) {
                        $docNo .= ' | Ref: ' . $inv->reference_number;
                    }
                    $customerName = $inv->customer->name ?? '';
                    $date = $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') : optional($inv->created_at)->format('Y-m-d');
                    $quantityFromSource = $inv->sales_invoice_data->sum(function ($row) {
                        return (float) ($row->qty ?? 0);
                    });
                }
            }

            $netAmount = $amountFromSource + (float) ($item->tax_amount ?? 0);

            return (object) [
                'reference_id' => $item->reference_id,
                'reference_type' => $item->reference_type,
                'number' => $docNo,
                'date' => $date,
                'customer_name' => $customerName,
                'amount' => $amountFromSource,
                'quantity' => $quantityFromSource,
                'tax_id' => $item->tax_id,
                'tax_amount' => $item->tax_amount,
                'net_amount' => $netAmount,
                'line_desc' => $item->line_desc,
            ];
        })->values();

        $advanceItems = $receiptVoucher->advances->map(function ($adv) {
            return (object) [
                'reference_id' => 0,
                'reference_type' => 'advance',
                'adv_no' => $adv->adv_no,
                'number' => $adv->adv_no,
                'date' => $adv->created_at->format('Y-m-d'),
                'customer_name' => $adv->customer->name ?? 'N/A',
                'amount' => $adv->amount,
                'quantity' => $adv->amount,
                'tax_id' => $adv->tax_id,
                'tax_amount' => $adv->tax_amount,
                'net_amount' => $adv->net_amount,
                'line_desc' => $adv->line_desc,
            ];
        });

        $initialItems = $initialItems->concat($advanceItems);

        $isAdvance = $receiptVoucher->items->contains(function ($item) {
            return $item->reference_type === 'sale_order';
        });

        $bankDetails = $receiptVoucher->bankDetails;
        $advanceAdjustments = \App\Models\CustomerAdvanceAdjustment::where("voucher_no", $receiptVoucher->unique_no)->get();

        $customerAdvances = \App\Models\CustomerAdvance::where("customer_id", $receiptVoucher->customer_id)
            ->where(function ($q) use ($receiptVoucher) {
                $q->whereIn("status", ["pending", "partial_payment"])
                  ->orWhereHas('adjustments', function ($adjQuery) use ($receiptVoucher) {
                      $adjQuery->where('voucher_no', $receiptVoucher->unique_no);
                  });
            })->get()->map(function ($adv) use ($receiptVoucher) {
                $remaining = (float)$adv->remaining_amount;
                $adjustment = \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $adv->id)
                    ->where('voucher_no', $receiptVoucher->unique_no)
                    ->sum('amount');
                $remaining += (float)$adjustment;
                return (object)[
                    "id" => $adv->id,
                    "text" => $adv->voucher_no . " - " . number_format($remaining, 2),
                    "remaining_amount" => $remaining
                ];
            });

        return view("management.finance.receipt_voucher.edit", [
            "receiptVoucher" => $receiptVoucher,
            "customers" => $customers,
            "accounts" => $accounts,
            "saleOrders" => $saleOrders,
            "salesInvoices" => $salesInvoices,
            "taxes" => $taxes,
            "initialItems" => $initialItems,
            "selectedReferences" => $selectedReferences,
            "isAdvance" => $isAdvance,
            "bankDetails" => $bankDetails,
            "advanceAdjustments" => $advanceAdjustments,
            "customerAdvances" => $customerAdvances
        ]);
    }

    public function edit_direct($id)
    {
        $receiptVoucher = ReceiptVoucher::with('items')->findOrFail($id);
        $accounts = Account::all();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        return view("management.finance.receipt_voucher.edit_directReceiptVoucher", compact("receiptVoucher", "taxes", "accounts"));
    }

    public function update_direct(Request $request, $id)
    {
        // TODO: Create a validation request class if needed, similar to ReceiptVoucherRequest
        // For now, assuming basic validation
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'rv_date' => 'required|date',
            'unique_no' => 'required|string',
            'account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'account.*' => 'required|exists:accounts,id',
            'amount.*' => 'required|numeric|min:0',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'tax_amount.*' => 'nullable|numeric|min:0',
            'net_amount.*' => 'nullable|numeric|min:0',
            'description.*' => 'nullable|string',
        ]);

        $payload = $request->all();
        $receiptVoucher = ReceiptVoucher::findOrFail($id);

        DB::beginTransaction();
        try {
            // Calculate items and totals
            $accounts_array = $payload['account'] ?? [];
            $amounts = $payload['amount'] ?? [];
            $tax_ids = $payload['tax_id'] ?? [];
            $tax_amounts = $payload['tax_amount'] ?? [];
            $net_amounts = $payload['net_amount'] ?? [];
            $descriptions = $payload['description'] ?? [];

            $totalNetAmount = 0;
            $items = [];

            for ($i = 0; $i < count($accounts_array); $i++) {
                if (empty($accounts_array[$i]) || empty($amounts[$i]))
                    continue;

                $amount = (float) ($amounts[$i] ?? 0);
                $tax_id = $tax_ids[$i] ?? null;
                $tax_amount = (float) ($tax_amounts[$i] ?? 0);
                $net_amount = (float) ($net_amounts[$i] ?? ($amount + $tax_amount));
                $description = $descriptions[$i] ?? null;

                $totalNetAmount += $net_amount;

                $items[] = [
                    'account_id' => $accounts_array[$i],
                    'amount' => $amount,
                    'tax_id' => $tax_id,
                    'tax_amount' => $tax_amount,
                    'net_amount' => $net_amount,
                    'line_desc' => $description,
                    'reference_id' => 1234,
                    'reference_type' => "direct"
                ];
            }

            if (empty($items)) {
                throw new \Exception('At least one valid voucher entry is required.');
            }

            // Delete old transactions
            $oldPurpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
            Transaction::where('purpose', $oldPurpose)->delete();

            // Update the receipt voucher
            $receiptVoucher->update([
                'unique_no' => $payload['unique_no'],
                'rv_date' => $payload['rv_date'],
                'ref_bill_no' => $payload['ref_bill_no'] ?? null,
                'bill_date' => $payload['bill_date'] ?? null,
                'account_id' => $payload['account_id'],
                'voucher_type' => $payload['voucher_type'],
                'remarks' => $payload['remarks'] ?? null, // Assuming optional, as in create
                'total_amount' => $totalNetAmount,
                'company_id' => $request->company_id, // Assuming same as create
            ]);

            // Delete old items
            $receiptVoucher->items()->delete();

            // Create new items
            foreach ($items as $item) {
                ReceiptVoucherItem::create(array_merge($item, ['receipt_voucher_id' => $receiptVoucher->id]));
            }

            // Create new transactions
            // For direct receipt: Debit the main account (bank/cash) for each item's net_amount, credit the item's account
            // This creates balanced pairs per item
            $purpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
            $remarks = $payload['remarks'] ?? null;
            $unique_no = $receiptVoucher->unique_no;
            $company_id = $request->company_id; // Assuming from request, as in create

            foreach ($items as $item) {
                $net_amount = $item['net_amount'];
                $item_account_id = $item['account_id'];
                $main_account_id = $payload['account_id'];

                // Debit main account (bank/cash)
                createTransaction(
                    $net_amount,
                    $main_account_id,
                    $company_id,
                    $unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => $purpose,
                        'payment_against' => $unique_no,
                        'counter_account_id' => $item_account_id,
                        'remarks' => $remarks
                    ]
                );

                // Credit item account
                createTransaction(
                    $net_amount,
                    $item_account_id,
                    $company_id,
                    $unique_no,
                    'credit',
                    'no',
                    [
                        'purpose' => $purpose,
                        'payment_against' => $unique_no,
                        'counter_account_id' => $main_account_id,
                        'remarks' => $remarks
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }

        return response()->json([
            'success' => 'Direct Receipt Voucher updated successfully!',
            'redirect' => route('receipt-voucher.index')
        ]);
    }

    public function store(ReceiptVoucherRequest $request)
    {
        $payload = $request->validated();
        $items = collect($payload['items'] ?? [])
            ->filter(function ($item) {
                return !empty($item['reference_id']) && !empty($item['reference_type']);
            });

        $hasRealReference = $items->contains(function ($item) {
            return in_array($item['reference_type'], ['sale_order', 'sales_invoice']);
        });

        if (!$hasRealReference) {
            return response()->json('Please select at least one Sale Order or Sales Invoice.', 422);
        }

        DB::beginTransaction();
        try {
            $totalAmount = $items->sum(function ($item) {
                return (float) ($item['amount'] ?? 0);
            });

            $totalNetAmount = $items->sum(function ($item) {
                $amount = (float) ($item['amount'] ?? 0);
                $taxAmount = (float) ($item['tax_amount'] ?? 0);
                return $item['net_amount'] ?? ($amount + $taxAmount);
            });


            // Validation: Bank Details Total <= Reference Total
            $totalBankDetailsAmount = collect($request->bank_details ?? [])
                ->sum(fn($detail) => (float) ($detail['amount'] ?? 0));

            // if ($totalBankDetailsAmount > $totalNetAmount) {
            //     throw new \Exception("Total Bank/Account amount ($totalBankDetailsAmount) cannot exceed the total Selected References amount ($totalNetAmount).");
            // }

            $customer = Customer::with('account')->findOrFail($payload['customer_id']);
            $customerAccountId = $customer->account_id;
            if (!$customerAccountId) {
                throw new \Exception('Selected customer has no linked account.');
            }


            $receiptVoucher = ReceiptVoucher::create([
                'unique_no' => $payload['unique_no'],
                'rv_date' => $payload['rv_date'],
                'ref_bill_no' => $payload['ref_bill_no'] ?? null,
                'bill_date' => $payload['bill_date'] ?? null,
                'cheque_no' => $payload['cheque_no'] ?? null,
                'cheque_date' => $payload['cheque_date'] ?? null,
                'account_id' => $payload['account_id'],
                'customer_id' => $payload['customer_id'] ?? null,
                'voucher_type' => $payload['voucher_type'],
                'remarks' => $payload['remarks'] ?? null,
                'total_amount' => $totalNetAmount,
                "is_direct" => 0,
                "company_id" => $request->company_id,
                "allow_excess_amount" => $request->allow_excess ? 1 : 0,
            ]);

            $totalExcessAmount = 0;
            foreach ($items as $item) {
                if ($item['reference_type'] === 'advance') {
                    $receiptVoucher->advances()->create([
                        'customer_id' => $payload['customer_id'],
                        'adv_no' => $item['adv_no'] ?? 'ADV-001',
                        'amount' => $item['amount'] ?? 0,
                        'tax_id' => $item['tax_id'] ?? null,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'net_amount' => $item['net_amount'] ?? ($item['amount'] ?? 0),
                        'line_desc' => $item['line_desc'] ?? null,
                    ]);
                } else {
                    $balance = receipt_voucher_balance($item['reference_id'], $item['reference_type']);
                    $excessAmount = $item['amount'] - $balance;
                    if ($excessAmount > 0) {
                        if (!$request->allow_excess) {
                            throw new \Exception("Paid amount (" . $item['amount'] . ") for " . ucwords(str_replace('_', ' ', $item['reference_type'])) . " ID: " . $item['reference_id'] . " exceeds its remaining balance (" . $balance . "). Please enable 'Allow Excess Amount' or reduce the amount.");
                        }

                        $tax_amount = $item["tax_id"] ? Tax::find($item['tax_id'])->percentage * $excessAmount / 100 : 0;
                        $totalExcessAmount += ($excessAmount + $tax_amount);
                        $notAllocatedItem = ReceiptVoucherItem::create([
                            'receipt_voucher_id' => $receiptVoucher->id,
                            'reference_type' => 'not-allocated',
                            'reference_id' => 0,
                            'amount' => $excessAmount,
                            'tax_id' => $item['tax_id'] ?? null,
                            'account_id' => null,
                            // Apply Tax on exceed amount
                            'tax_amount' => $tax_amount,
                            'net_amount' => $excessAmount + $tax_amount,
                            'line_desc' => $item['line_desc'] ?? null,
                            'customer_id' => $payload["customer_id"]
                        ]);

                        $transaction = createTransaction(
                            $excessAmount + $tax_amount,
                            $customerAccountId,
                            $request->company_id,
                            "-",
                            'credit',
                            'no',
                            [
                                'purpose' => "Extra Amount Received (Item #{$notAllocatedItem->id}) for the customer " . $customer->name,
                                'payment_against' => $receiptVoucher->unique_no,
                                'counter_account_id' => $payload['account_id'],
                                'remarks' => "Customer advance created from excess payment against Receipt Voucher " . $receiptVoucher->unique_no,
                                'receipt_voucher_item_id' => $notAllocatedItem->id
                            ]
                        );

                        \App\Models\CustomerAdvance::create([
                            "customer_id" => $payload["customer_id"],
                            "source_type" => "excess_payment",
                            "payment_type" => $payload["voucher_type"],
                            "voucher_no" => $receiptVoucher->unique_no,
                            "amount" => $excessAmount + $tax_amount,
                            "used_amount" => 0,
                            "remaining_amount" => $excessAmount + $tax_amount,
                            "status" => "pending"
                        ]);
                    }

                    $excess = $excessAmount < 0 ? 0 : $excessAmount;



                    $amount = $item['amount'] ?? 0;

                    $taxAmount = $item['tax_amount'] ?? 0;
                    $netAmount = $item['net_amount'] ?? $amount;

                    ReceiptVoucherItem::create([
                        'receipt_voucher_id' => $receiptVoucher->id,
                        'reference_type' => $item['reference_type'],
                        'reference_id' => $item['reference_id'],

                        // safe subtraction
                        'amount' => max(0, $amount - $excess),
                        'tax_amount' => max(0, $taxAmount - $excess),
                        'net_amount' => max(0, $netAmount - $excess),

                        'tax_id' => $item['tax_id'] ?? null,
                        'account_id' => null,
                        'line_desc' => $item['line_desc'] ?? null,
                        'customer_id' => $payload['customer_id'],
                    ]);
                }
            }

            // Create transactions (debit bank/cash, credit customer)
            $purpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
            $remarks = $payload['remarks'] ?? null;

            // Save Bank Details and create Debit Transactions
            if ($request->filled('bank_details')) {
                foreach ($request->bank_details as $detail) {
                    if (!empty($detail['account_id']) && !empty($detail['amount'])) {
                        $receiptVoucher->bankDetails()->create([
                            'account_id' => $detail['account_id'],
                            'amount' => $detail['amount'],
                            'cheque_no' => $detail['cheque_no'] ?? null,
                        ]);

                        createTransaction(
                            $detail['amount'],
                            $detail['account_id'],
                            $request->company_id,
                            $receiptVoucher->unique_no,
                            'debit',
                            'no',
                            [
                                'purpose' => $purpose,
                                'payment_against' => $receiptVoucher->unique_no,
                                'counter_account_id' => $customerAccountId,
                                'remarks' => $remarks . ($detail['cheque_no'] ? " (Cheque: {$detail['cheque_no']})" : "")
                            ]
                        );
                    }
                }
            } else {
                createTransaction(
                    $totalNetAmount,
                    $payload['account_id'],
                    $request->company_id,
                    $receiptVoucher->unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => $purpose,
                        'payment_against' => $receiptVoucher->unique_no,
                        'counter_account_id' => $customerAccountId,
                        'remarks' => $remarks
                    ]
                );
            }

            // Always credit the customer for the total net amount

            createTransaction(
                $totalNetAmount - $totalExcessAmount,
                $customerAccountId,
                $request->company_id,
                $receiptVoucher->unique_no,
                'credit',
                'no',
                [
                    'purpose' => $purpose,
                    'payment_against' => $receiptVoucher->unique_no,
                    'counter_account_id' => $payload['account_id'],
                    'remarks' => $remarks
                ]
            );

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }

        return response()->json([
            'success' => 'Receipt voucher created successfully!',
            'redirect' => route('receipt-voucher.index')
        ]);
    }

    public function directReceiptVoucher(Request $request)
    {
        $accounts = Account::all();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        return view("management.finance.receipt_voucher.directReceiptVoucher", compact("taxes", "accounts"));
    }

    public function getCustomerAdvances(Request $request)
    {
        $customerId = $request->input("customer_id");
        $voucherNo = $request->input("voucher_no");

        $advances = \App\Models\CustomerAdvance::where("customer_id", $customerId)
            ->where(function ($q) use ($voucherNo) {
                $q->whereIn("status", ["pending", "partial_payment"]);
                if ($voucherNo) {
                    $q->orWhereHas('adjustments', function ($adjQuery) use ($voucherNo) {
                        $adjQuery->where('voucher_no', $voucherNo);
                    });
                }
            })->get();

        $formatted = $advances->map(function ($adv) use ($voucherNo) {
            $remaining = (float)$adv->remaining_amount;
            if ($voucherNo) {
                $adjustment = \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $adv->id)
                    ->where('voucher_no', $voucherNo)
                    ->sum('amount');
                $remaining += (float)$adjustment;
            }

            return [
                "id" => $adv->id,
                "remaining_amount" => $remaining,
                "voucher_no" => $adv->voucher_no,
                "text" => $adv->voucher_no . " - " . number_format($remaining, 2)
            ];
        });

        return response()->json(["advances" => $formatted]);
    }

    public function generateRvNumber(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'rv_date' => 'nullable|date'
        ]);

        $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BRV' : 'CRV';
        $prefixForAccounts = $request->voucher_type === 'bank_payment_voucher' ? '1-1' : '1-4';

        $accounts = Account::whereHas('parent', function ($query) use ($prefixForAccounts) {
            $query->where('hierarchy_path', $prefixForAccounts);
        })->get();

        $rvDate = $request->rv_date ? date('m-d-Y', strtotime($request->rv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $rvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('receipt_vouchers', $datePrefix, null, 'unique_no', false);

        return response()->json([
            'success' => true,
            'rv_number' => $uniqueNo,
            'accounts' => $accounts
        ]);
    }

    public function getReferenceDetails(Request $request)
    {
        $request->validate([
            'reference_type' => 'required|in:sale_order,sales_invoice',
            'reference_ids' => 'required|array|min:1',
            'reference_ids.*' => 'integer'
        ]);

        $referenceType = $request->reference_type;
        $ids = $request->reference_ids;
        $items = collect();

        if ($referenceType === 'sale_order') {
            $items = SalesOrder::with(['customer', 'sales_order_data'])
                ->whereIn('id', $ids)
                ->get()
                ->map(function ($order) {
                    $quantity = $order->sales_order_data->sum(function ($row) {
                        return (float) ($row->qty * $row->rate ?? 0);
                    });

                    return [
                        'reference_id' => $order->id,
                        'reference_type' => 'sale_order',
                        'number' => $order->reference_no ?? ('SO-' . $order->id),
                        'date' => $order->order_date
                            ? Carbon::parse($order->order_date)->format('Y-m-d')
                            : optional($order->created_at)->format('Y-m-d'),
                        'customer' => $order->customer->name ?? 'N/A',
                        'customer_name' => $order->customer->name ?? 'N/A',
                        'amount' => round($quantity, 2),
                        'quantity' => round($quantity, 2),
                    ];
                });
        } else {
            $items = SalesInvoice::with(['customer', 'sales_invoice_data'])
                ->whereIn('id', $ids)
                ->get()
                ->map(function ($invoice) {
                    $quantity = $invoice->sales_invoice_data->sum(function ($row) {
                        return (float) ($row->net_amount ?? 0);
                    });

                    return [
                        'reference_id' => $invoice->id,
                        'reference_type' => 'sales_invoice',
                        'number' => $invoice->si_no ?? ('INV-' . $invoice->id),
                        'date' => $invoice->invoice_date
                            ? Carbon::parse($invoice->invoice_date)->format('Y-m-d')
                            : optional($invoice->created_at)->format('Y-m-d'),
                        'customer' => $invoice->customer->name ?? 'N/A',
                        'customer_name' => $invoice->customer->name ?? 'N/A',
                        'amount' => round($quantity, 2),
                        'quantity' => round($quantity, 2),
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    public function show($id)
    {
        $receiptVoucher = ReceiptVoucher::with(['account', 'customer', 'items.account', 'advances.customer', 'bankDetails.account'])->findOrFail($id);

        // resolve items
        $standardItems = $receiptVoucher->items->map(function ($item) {
            $docNo = '';
            $customer = '';
            if ($item->reference_type === 'sale_order') {
                $so = SalesOrder::with('customer')->find($item->reference_id);
                $docNo = $so->reference_no ?? ('SO-' . $item->reference_id);
                $customer = $so->customer->name ?? '';
            } elseif ($item->reference_type === 'sales_invoice') {
                $inv = SalesInvoice::with('customer')->find($item->reference_id);
                $docNo = $inv->si_no ?? ('INV-' . $item->reference_id);
                if ($inv && $inv->reference_number) {
                    $docNo .= ' | Ref: ' . $inv->reference_number;
                }
                $customer = $inv->customer->name ?? '';
            } elseif ($item->reference_type === 'direct') {
                $docNo = 'Direct';
                $customer = $item->account->name ?? 'N/A';
            }

            return [
                'type' => $item->reference_type === 'sale_order' ? 'Sale Order' : ($item->reference_type === 'sales_invoice' ? 'Sale Invoice' : 'Direct RV'),
                'doc_no' => $docNo,
                'customer' => $customer,
                'amount' => $item->amount,
                'tax_amount' => $item->tax_amount,
                'net_amount' => $item->amount + $item->tax_amount,
                'line_desc' => $item->line_desc,
            ];
        });

        // resolve advances
        $advanceItems = $receiptVoucher->advances->map(function ($adv) {
            return [
                'type' => 'Advance',
                'doc_no' => '-',
                'customer' => $adv->customer->name ?? 'N/A',
                'amount' => $adv->amount,
                'tax_amount' => $adv->tax_amount,
                'net_amount' => $adv->net_amount ?: ($adv->amount + $adv->tax_amount),
                'line_desc' => $adv->line_desc,
            ];
        });

        $items = $standardItems->concat($advanceItems);

        return view('management.finance.receipt_voucher.show', [
            'receiptVoucher' => $receiptVoucher,
            'items' => $items
        ]);
    }



        public function update(Request $request, $id)
    {
        $receiptVoucher = ReceiptVoucher::findOrFail($id);
        $payload = app(\App\Http\Requests\Finance\ReceiptVoucherRequest::class)->validated();
        
        $items = collect($payload["items"] ?? [])
            ->filter(function ($item) {
                return !empty($item["reference_id"]) && !empty($item["reference_type"]);
            });

        $hasRealReference = $items->contains(function ($item) {
            return in_array($item["reference_type"], ["sale_order", "sales_invoice"]);
        });

        if (!$hasRealReference) {
            return response()->json(["message" => "Please select at least one Sale Order or Sales Invoice."], 422);
        }

        DB::beginTransaction();
        try {
            // Revert previous advances created by this voucher
            $generatedAdvances = \App\Models\CustomerAdvance::where("voucher_no", $receiptVoucher->unique_no)->get();
            foreach ($generatedAdvances as $adv) {
                if ($adv->used_amount > 0) {
                    throw new \Exception("Cannot edit this voucher because the excess advance generated by it has already been partially or fully consumed.");
                }
                $adv->delete();
            }

            // Revert consumed advances by this voucher
            $adjustments = \App\Models\CustomerAdvanceAdjustment::where("voucher_no", $receiptVoucher->unique_no)->get();
            foreach ($adjustments as $adj) {
                $adv = \App\Models\CustomerAdvance::find($adj->customer_advance_id);
                if ($adv) {
                    $adv->used_amount -= $adj->amount;
                    $adv->remaining_amount += $adj->amount;
                    $adv->status = ($adv->used_amount == 0) ? "pending" : "partial_payment";
                    $adv->save();
                }
                $adj->delete();
            }

            // Delete old details
            $receiptVoucher->bankDetails()->delete();
            ReceiptVoucherItem::where("receipt_voucher_id", $receiptVoucher->id)->delete();
            $receiptVoucher->advances()->delete();
            Transaction::where("voucher_no", $receiptVoucher->unique_no)->delete();

            // Calculate new totals
            $totalAmount = $items->sum(function ($item) {
                return (float) ($item["amount"] ?? 0);
            });

            $totalNetAmount = $items->sum(function ($item) {
                $amount = (float) ($item["amount"] ?? 0);
                $taxAmount = (float) ($item["tax_amount"] ?? 0);
                return $item["net_amount"] ?? ($amount + $taxAmount);
            });

            $customer = Customer::with("account")->findOrFail($payload["customer_id"]);
            $customerAccountId = $customer->account_id;
            if (!$customerAccountId) {
                throw new \Exception("Selected customer has no linked account.");
            }

            $receiptVoucher->update([
                "unique_no" => $payload["unique_no"],
                "rv_date" => $payload["rv_date"],
                "ref_bill_no" => $payload["ref_bill_no"] ?? null,
                "bill_date" => $payload["bill_date"] ?? null,
                "cheque_no" => $payload["cheque_no"] ?? null,
                "cheque_date" => $payload["cheque_date"] ?? null,
                "account_id" => $payload["account_id"],
                "customer_id" => $payload["customer_id"] ?? null,
                "voucher_type" => $payload["voucher_type"],
                "remarks" => $payload["remarks"] ?? null,
                "total_amount" => $totalNetAmount,
                "company_id" => $request->company_id,
                "allow_excess_amount" => $request->allow_excess ? 1 : 0,
                "am_approval_status" => 'pending',
                "am_change_made" => 1,
            ]);

            $totalExcessAmount = 0;
            foreach ($items as $item) {
                if ($item["reference_type"] === "advance") {
                    $receiptVoucher->advances()->create([
                        "customer_id" => $payload["customer_id"],
                        "adv_no" => $item["adv_no"] ?? "ADV-001",
                        "amount" => $item["amount"] ?? 0,
                        "tax_id" => $item["tax_id"] ?? null,
                        "tax_amount" => $item["tax_amount"] ?? 0,
                        "net_amount" => $item["net_amount"] ?? ($item["amount"] ?? 0),
                        "line_desc" => $item["line_desc"] ?? null,
                    ]);
                } else {
                    $balance = receipt_voucher_balance($item["reference_id"], $item["reference_type"]);
                    $excessAmount = $item["amount"] - $balance;

                    if ($excessAmount > 0) {
                        if (!$request->allow_excess) {
                            throw new \Exception("Paid amount (" . $item["amount"] . ") for " . ucwords(str_replace("_", " ", $item["reference_type"])) . " ID: " . $item["reference_id"] . " exceeds its remaining balance (" . $balance . "). Please enable 'Allow Excess Amount' or reduce the amount.");
                        }

                        $tax_amount = $item["tax_id"] ? Tax::find($item["tax_id"])->percentage * $excessAmount / 100 : 0;
                        $totalExcessAmount += ($excessAmount + $tax_amount);

                        $notAllocatedItem = ReceiptVoucherItem::create([
                            "receipt_voucher_id" => $receiptVoucher->id,
                            "reference_type" => "not-allocated",
                            "reference_id" => 0,
                            "amount" => $excessAmount,
                            "tax_id" => $item["tax_id"] ?? null,
                            "account_id" => null,
                            "tax_amount" => $tax_amount,
                            "net_amount" => $excessAmount + $tax_amount,
                            "line_desc" => $item["line_desc"] ?? null,
                            "customer_id" => $payload["customer_id"]
                        ]);

                        $transaction = createTransaction(
                            $excessAmount + $tax_amount,
                            $customerAccountId,
                            $request->company_id,
                            "-",
                            "credit",
                            "no",
                            [
                                "purpose" => "Extra Amount Received (Item #{$notAllocatedItem->id}) for the customer " . $customer->name,
                                "payment_against" => $receiptVoucher->unique_no,
                                "counter_account_id" => $payload["account_id"],
                                "remarks" => "",
                                "receipt_voucher_item_id" => $notAllocatedItem->id
                            ]
                        );

                        \App\Models\CustomerAdvance::create([
                            "customer_id" => $payload["customer_id"],
                            "source_type" => "excess_payment",
                            "payment_type" => $payload["voucher_type"],
                            "voucher_no" => $receiptVoucher->unique_no,
                            "amount" => $excessAmount + $tax_amount,
                            "used_amount" => 0,
                            "remaining_amount" => $excessAmount + $tax_amount,
                            "status" => "pending"
                        ]);
                    }

                    $excess = $excessAmount < 0 ? 0 : $excessAmount;

                    $amount = $item["amount"] ?? 0;
                    $taxAmount = $item["tax_amount"] ?? 0;
                    $netAmount = $item["net_amount"] ?? $amount;

                    ReceiptVoucherItem::create([
                        "receipt_voucher_id" => $receiptVoucher->id,
                        "reference_type" => $item["reference_type"],
                        "reference_id" => $item["reference_id"],
                        "amount" => max(0, $amount - $excess),
                        "tax_amount" => max(0, $taxAmount - $excess),
                        "net_amount" => max(0, $netAmount - $excess),
                        "tax_id" => $item["tax_id"] ?? null,
                        "account_id" => null,
                        "line_desc" => $item["line_desc"] ?? null,
                        "customer_id" => $payload["customer_id"],
                    ]);
                }
            }

            $purpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
            $remarks = $payload["remarks"] ?? null;

            $totalAdvanceConsumed = 0;
            if ($request->filled("bank_details")) {
                foreach ($request->bank_details as $detail) {
                    if (!empty($detail["customer_advance_id"]) && !empty($detail["amount"])) {
                        $advance = \App\Models\CustomerAdvance::find($detail["customer_advance_id"]);
                        if ($advance) {
                            $adjAmount = $detail["amount"];
                            if ($adjAmount > $advance->remaining_amount) {
                                throw new \Exception("Cannot consume more than the remaining amount of the advance.");
                            }
                            $advance->used_amount += $adjAmount;
                            $advance->remaining_amount -= $adjAmount;
                            $advance->status = ($advance->remaining_amount <= 0 && $advance->used_amount == $advance->amount) ? "completed" : "partial_payment";
                            $advance->save();

                            \App\Models\CustomerAdvanceAdjustment::create([
                                "customer_advance_id" => $advance->id,
                                "voucher_no" => $receiptVoucher->unique_no,
                                "amount" => $adjAmount
                            ]);
                            $totalAdvanceConsumed += $adjAmount;
                        }
                    } elseif (!empty($detail["account_id"]) && !empty($detail["amount"])) {
                        $receiptVoucher->bankDetails()->create([
                            "account_id" => $detail["account_id"],
                            "amount" => $detail["amount"],
                            "cheque_no" => $detail["cheque_no"] ?? null,
                        ]);

                        createTransaction(
                            $detail["amount"],
                            $detail["account_id"],
                            $request->company_id,
                            $receiptVoucher->unique_no,
                            "debit",
                            "no",
                            [
                                "purpose" => $purpose,
                                "payment_against" => $receiptVoucher->unique_no,
                                "counter_account_id" => $customerAccountId,
                                "remarks" => $remarks . ($detail["cheque_no"] ? " (Cheque: {$detail["cheque_no"]})" : "")
                            ]
                        );
                    }
                }
            } else {
                createTransaction(
                    $totalNetAmount,
                    $payload["account_id"],
                    $request->company_id,
                    $receiptVoucher->unique_no,
                    "debit",
                    "no",
                    [
                        "purpose" => $purpose,
                        "payment_against" => $receiptVoucher->unique_no,
                        "counter_account_id" => $customerAccountId,
                        "remarks" => $remarks
                    ]
                );
            }

            $creditAmount = $totalNetAmount - $totalAdvanceConsumed;

            if ($creditAmount > 0) {
                createTransaction(
                    $creditAmount,
                    $customerAccountId,
                    $request->company_id,
                    $receiptVoucher->unique_no,
                    "credit",
                    "no",
                    [
                        "purpose" => $purpose,
                        "payment_against" => $receiptVoucher->unique_no,
                        "counter_account_id" => $payload["account_id"],
                        "remarks" => $remarks
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["message" => $th->getMessage()], 500);
        }

        return response()->json([
            "success" => "Receipt voucher updated successfully!",
            "redirect" => route("receipt-voucher.index")
        ]);
    }
public function destroy($id)
    {
        abort(404);
    }
}
