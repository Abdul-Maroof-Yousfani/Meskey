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

    public function getitems(Request $request) {
        $items = json_decode($request->items);
        $taxes = Tax::where("status", "active")->get();

        return view("management.finance.receipt_voucher.getItems", compact("items", "taxes"));
    }


    public function direct_receipt_voucher(DirectReceiptVoucherRequest $request) {
        DB::beginTransaction();
        try {
            $receipt_voucher = ReceiptVoucher::create([
                ...$request->validated(),
                "is_direct" => 1
            ]);
            $amount = 0;


            foreach($request->account as $index => $account) {

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
        } catch(Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getDocumentsForRv(Request $request) {
        $is_advance = $request->is_advance === 'true' ? true : false;
        $customer_id = $request->customer_id;
        $dropdowndData = [];
        $data = [];
       
        if($is_advance) {
            $dropdowndData[] = [
                "id" => "",
                "text" => "Select Sale Order"
            ];

            $data = SalesOrder::select("id", "reference_no")
                                    ->with("sales_order_data")
                                    ->where("customer_id", $customer_id)
                                    ->where("am_approval_status", 'approved')
                                    ->get()
                                    ->filter(function($saleOrder) {
                                        // Example: keep only if any related sale_order_data has quantity > 0
                                        return $saleOrder->sales_order_data->contains(function($item) {
                                            $balance = receipt_voucher_balance($item->sale_order_id);
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
                                    ->filter(function($sale_invoice) {
                                        // Example: keep only if any related sale_order_data has quantity > 0
                                        return $sale_invoice->sales_invoice_data->contains(function($item) {
                                            $balance = receipt_voucher_balance($item->sales_invoice_id, "sales_invoie");
                                            return $balance > 0; 
                                        });
                                    });
        }
        
      

        foreach($data as $datum) {
            $dropdowndData[] = [
                "id" => $datum->id,
                "text" => $datum->reference_no
            ];
            
        }


        return $dropdowndData;


    }

    public function getList(Request $request)
    {
        $receiptVouchers = ReceiptVoucher::with(['account', 'customer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('unique_no', 'like', $searchTerm)
                        ->orWhere('ref_bill_no', 'like', $searchTerm)
                        ->orWhere('cheque_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

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

        return view('management.finance.receipt_voucher.create', compact('customers', 'saleOrders', 'salesInvoices', 'taxes'));
    }

    public function edit($id)
    {
        $receiptVoucher = ReceiptVoucher::with(['items', 'account', 'customer'])->findOrFail($id);

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
        $initialItems = $receiptVoucher->items->map(function ($item) use (&$selectedReferences) {
            $selectedReferences[] = (string) $item->reference_id;
            $docNo = '';
            $customerName = '';
            $date = now()->format('Y-m-d');
            $amountFromSource = $item->amount;
            $quantityFromSource = 0;

            if ($item->reference_type === 'sale_order') {
                $so = SalesOrder::with(['customer', 'sales_order_data'])->find($item->reference_id);
                if ($so) {
                    $docNo = $so->so_reference_no ?? $so->reference_no ?? $so->so_no ?? ('SO-' . $so->id);
                    $customerName = $so->customer->name ?? '';
                    $date = $so->order_date ? Carbon::parse($so->order_date)->format('Y-m-d') : optional($so->created_at)->format('Y-m-d');
                    $quantityFromSource = $so->sales_order_data->sum(function ($row) {
                        return (float) ($row->qty ?? 0);
                    });
                    $amountFromSource = $quantityFromSource;
                }
            } else {
                $inv = SalesInvoice::with(['customer', 'sales_invoice_data'])->find($item->reference_id);
                if ($inv) {
                    $docNo = $inv->si_no ?? ('INV-' . $inv->id);
                    if ($inv->reference_number) {
                        $docNo .= ' | Ref: ' . $inv->reference_number;
                    }
                    $customerName = $inv->customer->name ?? '';
                    $date = $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('Y-m-d') : optional($inv->created_at)->format('Y-m-d');
                    $quantityFromSource = $inv->sales_invoice_data->sum(function ($row) {
                        return (float) ($row->qty ?? 0);
                    });
                    $amountFromSource = $quantityFromSource;
                }
            }

            $netAmount = $amountFromSource + (float) ($item->tax_amount ?? 0);

            return [
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

        $isAdvance = $receiptVoucher->items->contains(function ($item) {
            return $item->reference_type === 'sale_order';
        });

        return view('management.finance.receipt_voucher.edit', [
            'receiptVoucher' => $receiptVoucher,
            'customers' => $customers,
            'accounts' => $accounts,
            'saleOrders' => $saleOrders,
            'salesInvoices' => $salesInvoices,
            'taxes' => $taxes,
            'initialItems' => $initialItems,
            'selectedReferences' => $selectedReferences,
            'isAdvance' => $isAdvance,
        ]);
    }

    public function edit_direct($id){
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
            if (empty($accounts_array[$i]) || empty($amounts[$i])) continue;

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
            ]);

            foreach ($items as $item) {
                ReceiptVoucherItem::create([
                    'receipt_voucher_id' => $receiptVoucher->id,
                    'reference_type' => $item['reference_type'],
                    'reference_id' => $item['reference_id'],
                    'amount' => $item['amount'] ?? 0,
                    'tax_id' => $item['tax_id'] ?? null,
                    'account_id' => null,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'net_amount' => $item['net_amount'] ?? ($item['amount'] ?? 0),
                    'line_desc' => $item['line_desc'] ?? null,
                ]);
            }

            // Create transactions (debit bank/cash, credit customer)
            $purpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
            $remarks = $payload['remarks'] ?? null;

            createTransaction(
                $totalNetAmount,
                $payload['account_id'],
                1,
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

            createTransaction(
                $totalNetAmount,
                $customerAccountId,
                1,
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

    public function directReceiptVoucher(Request $request) {
        $accounts = Account::all();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        return view("management.finance.receipt_voucher.directReceiptVoucher", compact("taxes", "accounts"));
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
                        'number' => $order->so_no ?? ('SO-' . $order->id),
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
        $receiptVoucher = ReceiptVoucher::with(['account', 'customer', 'items'])->findOrFail($id);

        // resolve reference labels
        $items = $receiptVoucher->items->map(function ($item) {
            $docNo = '';
            $customer = '';
            if ($item->reference_type === 'sale_order') {
                $so = SalesOrder::with('customer')->find($item->reference_id);
                $docNo = $so->so_reference_no ?? $so->reference_no ?? $so->so_no ?? ('SO-' . $item->reference_id);
                $customer = $so->customer->name ?? '';
            } else {
                $inv = SalesInvoice::with('customer')->find($item->reference_id);
                $docNo = $inv->si_no ?? ('INV-' . $item->reference_id);
                if ($inv && $inv->reference_number) {
                    $docNo .= ' | Ref: ' . $inv->reference_number;
                }
                $customer = $inv->customer->name ?? '';
            }

            return [
                'type' => $item->reference_type === 'sale_order' ? 'Sale Order' : 'Sales Invoice',
                'doc_no' => $docNo,
                'customer' => $customer,
                'amount' => $item->amount,
                'tax_amount' => $item->tax_amount,
                'net_amount' => $item->net_amount ?: ($item->amount + $item->tax_amount),
                'line_desc' => $item->line_desc,
            ];
        });

        return view('management.finance.receipt_voucher.show', [
            'receiptVoucher' => $receiptVoucher,
            'items' => $items
        ]);
    }

   

    public function update(Request $request, $id)
{
    $receiptVoucher = ReceiptVoucher::findOrFail($id);
    $payload = app(ReceiptVoucherRequest::class)->validated();

    // Filter valid items
    $items = collect($payload['items'] ?? [])
        ->filter(fn($item) => !empty($item['reference_id']) && !empty($item['reference_type']));

    DB::beginTransaction();
    try {
        $totalNetAmount = $items->sum(function ($item) {
            $amount = (float) ($item['amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            return $item['net_amount'] ?? ($amount + $taxAmount);
        });

        $customer = Customer::with('account')->findOrFail($payload['customer_id']);
        $customerAccountId = $customer->account_id;
        if (!$customerAccountId) {
            throw new \Exception('Selected customer has no linked account.');
        }

        // Update receipt voucher
        $receiptVoucher->update([
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
        ]);
        
        // Delete old items and recreate
        ReceiptVoucherItem::where('receipt_voucher_id', $receiptVoucher->id)->delete();
        foreach ($request->items as $item) {
            ReceiptVoucherItem::create([
                'receipt_voucher_id' => $receiptVoucher->id,
                'reference_type' => $item['reference_type'],
                'reference_id' => $item['reference_id'],
                'amount' => $item["amount_display"],
                'tax_id' => $item['tax_id'] ?? null,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'net_amount' => $item['net_amount'] ?? ($item['amount'] ?? 0),
                'line_desc' => $item['line_desc'] ?? null,
            ]);
        }

        // Remove old transactions
        Transaction::where('voucher_no', $receiptVoucher->unique_no)->delete();

        $purpose = "RV-{$receiptVoucher->id}-{$receiptVoucher->unique_no}";
        $remarks = $payload['remarks'] ?? null;

        // Create debit transaction for account
        createTransaction(
            $totalNetAmount,
            $payload['account_id'],
            1,
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

        // Create credit transaction for customer
        createTransaction(
            $totalNetAmount,
            $customerAccountId,
            1,
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
        'success' => 'Receipt voucher updated successfully!',
        'redirect' => route('receipt-voucher.index')
    ]);
}


    public function destroy($id)
    {
        abort(404);
    }
}
