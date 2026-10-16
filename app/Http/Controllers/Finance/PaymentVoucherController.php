<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\BillPaymentVoucherData;
use App\Models\BrokerCompanyBankDetail;
use App\Models\BrokerOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\Supplier;
use App\Models\Master\Tax;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherDetail;
use App\Models\SettlementAdjustment;
use App\Models\PaymentVoucher;
use App\Models\PaymentVoucherData;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\SupplierCompanyBankDetail;
use App\Models\SupplierOwnerBankDetail;
use App\Models\TransporterCompanyBankDetail;
use App\Models\TransporterOwnerBankDetail;
use App\Models\VendorCompanyBankDetail;
use App\Models\VendorOwnerBankDetail;
use App\Models\CustomerAdvance;
use App\Models\CustomerAdvanceAdjustment;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderData;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderData;
use App\Models\Sales\DeliveryChallanData;
use App\Models\Master\Customer;
use App\Models\CustomerCompanyBankDetail;
use App\Models\CustomerOwnerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.finance.payment_voucher.index');
    }

    public function billPaymentVoucher()
    {
        $accounts = Account::where("hierarchy_path", "like", "2-2%")->get();
        $bank_accounts = Account::where("hierarchy_path", "like", "1-1%")
            ->whereNot("hierarchy_path", "1-1")
            ->get();

        $suppliers = Supplier::where("status", "active")->get();


        return view('management.finance.payment_voucher.billPaymentVoucher', compact('accounts', 'bank_accounts', 'suppliers'));
    }
    public function editPaymentVoucher(int $id)
    {
        $payment_voucher = PaymentVoucher::findOrFail($id);

        $status = strtolower($payment_voucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return redirect()->route('payment-voucher.index')->with('error', "Cannot edit a Payment Voucher that has been {$status}.");
        }

        if ($payment_voucher->module_type != 'bill_payment_voucher') {
            return back();
        }

        $accounts = Account::where("hierarchy_path", "like", "2-2%")->get();
        $bank_accounts = Account::where("hierarchy_path", "like", "1-1%")
            ->whereNot("hierarchy_path", "1-1")
            ->get();

        $suppliers = Supplier::where("status", "active")->get();



        $payment_voucher_data = BillPaymentVoucherData::where("payment_voucher_id", $payment_voucher->id)->get();
        return view("management.finance.payment_voucher.editBillPayment", compact("payment_voucher", "payment_voucher_data", "accounts", "bank_accounts", "suppliers"));
    }

    /**
     * Get list of payment vouchers.
     */
    public function getList(Request $request)
    {
        $paymentVouchers = PaymentVoucher::with(['account', 'bank_account'])
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

        return view('management.finance.payment_voucher.getList', compact('paymentVouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $data['accounts'] = Account::where('is_operational', 'yes')->get();

        $data['suppliers'] = Supplier::whereHas('arrivalPurchaseOrders', function ($query) {
            $query->whereHas('paymentRequestData.paymentRequests', function ($q) {
                $q->where('status', 'approved')
                    ->whereDoesntHave('paymentVoucherData');
            });
        })->latest()->get();

        $data['accounts'] = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Liabilities');
        })->where('is_operational', 'no')->get()->pluck('name');

        $data['accounts'] = Account::where('is_operational', 'yes')
            ->where('status', 'active')
            ->whereHas('parent', function ($q) {
                $q->where('hierarchy_path', '2')
                    ->orWhereHas('parent', function ($q2) {
                        $q2->where('hierarchy_path', '2')
                            ->orWhereHas('parent', function ($q3) {
                                $q3->where('hierarchy_path', '2');
                            });
                    });
            })
            ->get();

        return view('management.finance.payment_voucher.create', $data);
    }

    public function directPaymentVoucher()
    {
        $accounts = Account::orderBy('name')->get();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();
        $customerAccounts = $accounts;

        return view('management.finance.payment_voucher.directPaymentVoucher', compact('taxes', 'accounts', 'customerAccounts'));
    }

    public function direct_payment_voucher_store(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',

            'account.*' => 'required|exists:accounts,id',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
            $datePrefix = $prefix . '-' . date('m-d-Y', strtotime($request->pv_date)) . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id, // Cash/Bank account (from where payment is made)
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
                'total_amount' => 0,
                'is_direct' => 1, // Flag to identify direct vouchers
            ]);

            $totalAmount = 0;

            foreach ($request->account as $index => $accountId) {
                $amount = $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = $request->tax_amount[$index] ?? 0;
                $netAmount = $request->net_amount[$index] ?? $amount + $taxAmount;
                $desc = $request->description[$index] ?? '';

                // Create line item (similar to PaymentVoucherData)
                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'tax_id' => $taxId,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $netAmount,
                    'description' => $desc,
                    'reference_type' => 'direct',
                    'reference_id' => null,
                ]);

                $totalAmount += $netAmount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();

            DB::commit();

            \App\Services\NotificationService::sendPaymentVoucherNotification($paymentVoucher);

            return response()->json([
                'success' => 'Direct Payment Voucher created successfully!',
                'redirect' => route('payment-voucher.index'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function edit_direct(PaymentVoucher $payment_voucher)
    {
        if (!$payment_voucher->is_direct) {
            abort(404);
        }

        $status = strtolower($payment_voucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return redirect()->route('payment-voucher.index')->with('error', "Cannot edit a Payment Voucher that has been {$status}.");
        }

        $accounts = Account::orderBy('name')->get();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();

        if ($payment_voucher->module_type === 'dpv_for_sale') {
            $customerAccounts = $accounts;
            $items = $payment_voucher->paymentVoucherData()->with(['customerAdvance', 'salesOrder', 'deliveryOrder', 'journalVoucher'])->get();

            $firstItem = $items->first();
            if ($firstItem && ($firstItem->reference_type === 'journal_voucher' || $firstItem->journal_voucher_id)) {
                $saleMode = 'journal_voucher';
            } elseif ($firstItem && $firstItem->reference_type === 'customer_advance') {
                $saleMode = 'excess_amount';
            } else {
                $saleMode = 'delivery_order';
            }
            $customerAccountId = $payment_voucher->request_account_id ?? ($firstItem?->account_id);

            $customer = $this->resolveCustomerFromAccountId($customerAccountId);
            $customerBankAccounts = collect();
            if ($customer) {
                $customer->load(['companyBankDetails', 'ownerBankDetails']);
                foreach ($customer->companyBankDetails as $bank) {
                    $customerBankAccounts->push([
                        'id' => $bank->id,
                        'type' => 'company',
                        'title' => $customer->name ?? '',
                        'account_title' => $bank->account_title ?? '',
                        'account_number' => $bank->account_number ?? '',
                        'bank_name' => $bank->bank_name ?? '',
                        'branch_name' => $bank->branch_name ?? '',
                        'branch_code' => $bank->branch_code ?? '',
                    ]);
                }
                foreach ($customer->ownerBankDetails as $bank) {
                    $customerBankAccounts->push([
                        'id' => $bank->id,
                        'type' => 'owner',
                        'title' => $customer->name ?? '',
                        'account_title' => $bank->account_title ?? '',
                        'account_number' => $bank->account_number ?? '',
                        'bank_name' => $bank->bank_name ?? '',
                        'branch_name' => $bank->branch_name ?? '',
                        'branch_code' => $bank->branch_code ?? '',
                    ]);
                }
            }

            return view("management.finance.payment_voucher.editDirectPaymentVoucherSale", compact(
                "payment_voucher", "accounts", "taxes", "customerAccounts", "items", "saleMode", "customerAccountId", "customerBankAccounts"
            ));
        }

        // Load line items
        $items = $payment_voucher->paymentVoucherData; // assuming relation name

        return view("management.finance.payment_voucher.editDirectPaymentVoucher", compact("payment_voucher", "accounts", "taxes", "items"));
    }

    public function update_direct(Request $request, PaymentVoucher $payment_voucher)
    {
        if (!$payment_voucher->is_direct) {
            abort(404);
        }

        $status = strtolower($payment_voucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => "Cannot edit a Payment Voucher that has been {$status}."], 422);
        }

        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',

            'account.*' => 'required|exists:accounts,id',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $payment_voucher->update([
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
            ]);

            // Delete old items
            $payment_voucher->paymentVoucherData()->delete();

            $totalAmount = 0;

            foreach ($request->account as $index => $accountId) {
                $amount = $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = $request->tax_amount[$index] ?? 0;
                $netAmount = $request->net_amount[$index] ?? $amount + $taxAmount;
                $desc = $request->description[$index] ?? '';

                PaymentVoucherData::create([
                    'payment_voucher_id' => $payment_voucher->id,
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'tax_id' => $taxId,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $netAmount,
                    'description' => $desc,
                    'reference_type' => 'direct',
                    'reference_id' => null,
                ]);

                $totalAmount += $netAmount;
            }

            $payment_voucher->update(['total_amount' => $totalAmount]);

            // Note: Transactions are not reversed/updated here for simplicity.
            // In production, you may want to reverse old transactions and create new ones.

            DB::commit();

            return response()->json([
                'success' => 'Direct Payment Voucher updated successfully!',
                'redirect' => route('payment-voucher.index')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function resolveCustomerFromAccountId($accountId)
    {
        if (!$accountId) {
            return null;
        }

        $customer = Customer::where('account_id', $accountId)->first();
        if ($customer) {
            return $customer;
        }

        $customer = Customer::find($accountId);
        if ($customer) {
            return $customer;
        }

        $account = Account::find($accountId);
        if ($account) {
            if ($account->model_id) {
                $customer = Customer::find($account->model_id);
                if ($customer) {
                    return $customer;
                }
            }

            $customer = Customer::where('name', $account->name)->first();
            if ($customer) {
                return $customer;
            }

            $cleanName = trim(preg_replace('/[0-9]+$/', '', $account->name));
            if ($cleanName) {
                $customer = Customer::where('name', 'like', $cleanName . '%')->first();
                if ($customer) {
                    return $customer;
                }
            }
        }

        return null;
    }

    public function getCustomerExcessAdvances(Request $request, $accountId)
    {
        $customer = $this->resolveCustomerFromAccountId($accountId);
        if (!$customer) {
            return response()->json([
                'success' => true,
                'customer_advances' => []
            ]);
        }

        $excludeVoucherNo = $request->query('exclude_voucher_no');

        $customerAdvances = CustomerAdvance::where('customer_id', $customer->id)
            ->where(function ($q) {
                $q->where('source_type', '!=', 'excess_payment')
                  ->orWhereExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('receipt_vouchers')
                          ->whereColumn('receipt_vouchers.unique_no', 'customer_advances.voucher_no')
                          ->whereNull('receipt_vouchers.deleted_at')
                          ->where('receipt_vouchers.am_approval_status', 'approved');
                  });
            })
            ->where(function ($q) use ($excludeVoucherNo) {
                $q->whereIn('status', ['pending', 'partial_payment']);
                if ($excludeVoucherNo) {
                    $q->orWhereHas('adjustments', function ($adjQuery) use ($excludeVoucherNo) {
                        $adjQuery->where('voucher_no', $excludeVoucherNo);
                    });
                }
            })
            ->get()
            ->map(function ($adv) use ($excludeVoucherNo) {
                $remaining = (float) $adv->remaining_amount;
                if ($excludeVoucherNo) {
                    $adjustment = CustomerAdvanceAdjustment::where('customer_advance_id', $adv->id)
                        ->where('voucher_no', $excludeVoucherNo)
                        ->sum('amount');
                    $remaining += (float) $adjustment;
                }

                return [
                    'id' => $adv->id,
                    'voucher_no' => $adv->voucher_no,
                    'source_type' => $adv->source_type,
                    'amount' => (float) $adv->amount,
                    'used_amount' => (float) $adv->used_amount,
                    'remaining_amount' => round($remaining, 2),
                    'formatted_remaining' => number_format($remaining, 2),
                    'text' => $adv->voucher_no . ' (Remaining: Rs. ' . number_format($remaining, 2) . ')'
                ];
            })
            ->filter(function ($item) {
                return $item['remaining_amount'] > 0.01;
            })
            ->values();

        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_advances' => $customerAdvances
        ]);
    }

    public function getCustomerJournalVouchers(Request $request, $accountId)
    {
        $account = Account::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => true,
                'journal_vouchers' => []
            ]);
        }

        $customer = $this->resolveCustomerFromAccountId($accountId);
        $customerName = $customer ? $customer->name : $account->name;

        $accountIds = [$account->id];
        if ($customer && $customer->account_id && !in_array($customer->account_id, $accountIds)) {
            $accountIds[] = $customer->account_id;
        }

        $excludePvId = $request->query('exclude_pv_id');

        $jvDetails = \App\Models\JournalVoucherDetail::with(['journalVoucher'])
            ->whereIn('acc_id', $accountIds)
            ->where('credit_amount', '>', 0)
            ->whereHas('journalVoucher', function ($q) {
                $q->where('am_approval_status', 'approved');
            })
            ->latest('id')
            ->get();

        $availableJvs = [];

        foreach ($jvDetails as $detail) {
            $jv = $detail->journalVoucher;
            if (!$jv) continue;

            $creditAmount = (float) $detail->credit_amount;

            $spentQuery = DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('reference_id', $jv->id);

            if ($excludePvId) {
                $exPv = PaymentVoucher::find($excludePvId);
                if ($exPv) {
                    $spentQuery->where('voucher_no', '!=', $exPv->unique_no);
                }
            }
            $settlementSpent = (float) $spentQuery->sum('amount');

            $refundQuery = PaymentVoucherData::where(function ($q) use ($detail, $jv) {
                $q->where('journal_voucher_detail_id', $detail->id)
                  ->orWhere(function ($sq) use ($jv, $detail) {
                      $sq->where('journal_voucher_id', $jv->id)
                         ->where('account_id', $detail->acc_id);
                  })
                  ->orWhere(function ($sq) use ($detail) {
                      $sq->where('reference_type', 'journal_voucher')
                         ->where('reference_id', $detail->id);
                  });
            })
            ->whereHas('paymentVoucher', function ($q) {
                $q->whereNull('deleted_at')
                  ->where('am_approval_status', '!=', 'rejected');
            });

            if ($excludePvId) {
                $refundQuery->where('payment_voucher_id', '!=', $excludePvId);
            }

            $pvDataSpent = (float) $refundQuery->sum('net_amount');

            $alreadyRefunded = max($settlementSpent, $pvDataSpent);

            $remainingAmount = max(0, $creditAmount - $alreadyRefunded);

            if ($remainingAmount > 0.01) {
                $jvDateFormatted = $jv->jv_date ? $jv->jv_date->format('d-M-Y') : 'N/A';
                $availableJvs[] = [
                    'id' => $jv->id,
                    'jv_id' => $jv->id,
                    'jv_detail_id' => $detail->id,
                    'jv_no' => $jv->jv_no,
                    'jv_date' => $jvDateFormatted,
                    'credit_amount' => $creditAmount,
                    'remaining_amount' => round($remainingAmount, 2),
                    'formatted_remaining' => number_format($remainingAmount, 2),
                    'description' => $detail->description ?: ($jv->description ?: ''),
                    'text' => "{$jv->jv_no} ({$jvDateFormatted}) - Rem: Rs. " . number_format($remainingAmount, 2),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'customer_name' => $customerName,
            'journal_vouchers' => $availableJvs,
        ]);
    }

    public function getCustomerSalesOrders(Request $request, $accountId)
    {
        $customer = $this->resolveCustomerFromAccountId($accountId);
        if (!$customer) {
            return response()->json([
                'success' => true,
                'sales_orders' => []
            ]);
        }

        $salesOrders = SalesOrder::where('customer_id', $customer->id)
            ->where('am_approval_status', 'approved')
            ->select('id', 'reference_no', 'status')
            ->latest('id')
            ->get()
            ->map(function ($so) {
                $statusLabel = $so->status ? ' (' . ucfirst($so->status) . ')' : '';
                return [
                    'id' => $so->id,
                    'reference_no' => $so->reference_no,
                    'status' => $so->status,
                    'text' => $so->reference_no . $statusLabel
                ];
            });

        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'sales_orders' => $salesOrders
        ]);
    }

    public function getSalesOrderRemainingAdvance($salesOrder, $excludeVoucherNo = null, $excludePvId = null)
    {
        $soId = is_object($salesOrder) ? $salesOrder->id : $salesOrder;

        // 1. Total advance received for this SO from approved RVs
        $soRvAdvance = (float) DB::table('receipt_voucher_items')
            ->join('receipt_vouchers', 'receipt_vouchers.id', '=', 'receipt_voucher_items.receipt_voucher_id')
            ->whereNull('receipt_vouchers.deleted_at')
            ->where('receipt_vouchers.am_approval_status', 'approved')
            ->where('receipt_voucher_items.reference_type', 'sale_order')
            ->where('receipt_voucher_items.reference_id', $soId)
            ->sum('receipt_voucher_items.net_amount');

        // 2. Advance attached/consumed by Delivery Orders for this SO
        $soDoAdvanceConsumed = (float) DB::table('delivery_order_receipt_voucher as dorv')
            ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
            ->where('do_tbl.so_id', $soId)
            ->where('do_tbl.am_approval_status', '!=', 'rejected')
            ->sum('dorv.amount');

        // 3. Adjustments against this SO or its RVs from approved Journal Vouchers
        $rvIdsForSo = DB::table('receipt_voucher_items')
            ->where('reference_type', 'sale_order')
            ->where('reference_id', $soId)
            ->pluck('receipt_voucher_id')
            ->toArray();

        $soJvAdjusted = (float) DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where('journal_vouchers.am_approval_status', 'approved')
            ->where(function ($q) use ($soId, $rvIdsForSo) {
                $q->where('journal_voucher_details.sales_order_id', $soId)
                  ->whereNull('journal_voucher_details.delivery_order_id');
                if (!empty($rvIdsForSo)) {
                    $q->orWhere(function ($sub) use ($rvIdsForSo) {
                        $sub->whereIn('journal_voucher_details.receipt_voucher_id', $rvIdsForSo)
                            ->whereNull('journal_voucher_details.sales_order_id')
                            ->whereNull('journal_voucher_details.delivery_order_id');
                    });
                }
            })
            ->sum('journal_voucher_details.debit_amount');

        // 4. Previous direct DPV refunds for this SO
        $alreadyRefundedSo = (float) PaymentVoucherData::where('sales_order_id', $soId)
            ->whereNull('delivery_order_id')
            ->whereHas('paymentVoucher', function ($q) use ($excludeVoucherNo, $excludePvId) {
                $q->whereNull('deleted_at');
                $q->where('am_approval_status', '!=', 'rejected');
                if ($excludeVoucherNo) {
                    $q->where('unique_no', '!=', $excludeVoucherNo);
                }
                if ($excludePvId) {
                    $q->where('id', '!=', $excludePvId);
                }
            })
            ->sum('amount');

        $soRemainingAdvance = max(0, $soRvAdvance - $soDoAdvanceConsumed - $soJvAdjusted - $alreadyRefundedSo);
        return round($soRemainingAdvance, 2);
    }

    public function getSoDeliveryOrders(Request $request, $soId)
    {
        $salesOrder = SalesOrder::with(['sales_order_data.item'])->findOrFail($soId);
        $excludeVoucherNo = $request->query('exclude_voucher_no');

        // Fetch real DOs for this SO (both active and closed, where not rejected)
        $deliveryOrders = DeliveryOrder::with(['delivery_order_data.item'])
            ->where('so_id', $soId)
            ->where('am_approval_status', '!=', 'rejected')
            ->where(function ($q) {
                $q->whereNull('is_auto_created_from_so')
                    ->orWhere('is_auto_created_from_so', '!=', 1);
            })
            ->get();

        $dos = [];
        foreach ($deliveryOrders as $do) {
            // Active DOs with an associated Loading Program cannot be refunded via DPV unless closed
            if ($do->do_status === 'active' && delivery_order_has_loading_program($do->id)) {
                continue;
            }

            $totalDoQty = 0;
            $totalDcQty = 0;
            $totalRemainingQty = 0;
            $totalRemainingAmount = 0;
            $totalDispatchedValue = 0;

            foreach ($do->delivery_order_data as $itemData) {
                $iDoQty = (float) $itemData->qty;
                $iDcQty = (float) DeliveryChallanData::where('do_data_id', $itemData->id)
                    ->whereHas('deliveryChallan', function ($q) {
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('qty');

                $iRemQty = max(0, $iDoQty - $iDcQty);
                $iRate = (float) $itemData->rate;
                $iRemAmt = $iRemQty * $iRate;

                $totalDoQty += $iDoQty;
                $totalDcQty += $iDcQty;
                $totalRemainingQty += $iRemQty;
                $totalRemainingAmount += $iRemAmt;
                $totalDispatchedValue += $iDcQty * $iRate;
            }

            // Attached cash advance to this DO from approved RVs
            $attachedRvAmount = (float) DB::table('delivery_order_receipt_voucher as dorv')
                ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
                ->whereNull('rv.deleted_at')
                ->where('rv.am_approval_status', 'approved')
                ->where('dorv.delivery_order_id', $do->id)
                ->sum('dorv.amount');

            if ($attachedRvAmount > 0) {
                $remainingAttachedAdvance = max(0, $attachedRvAmount - $totalDispatchedValue);
                // Returnable cash advance takes precedence if higher than discrete bag rounding
                $totalRemainingAmount = max($totalRemainingAmount, $remainingAttachedAdvance);
            }

            // Deduct previous DPV refunds for this DO
            $alreadyRefunded = (float) PaymentVoucherData::where('delivery_order_id', $do->id)
                ->whereHas('paymentVoucher', function ($q) use ($excludeVoucherNo) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', '!=', 'rejected');
                    if ($excludeVoucherNo) {
                        $q->where('unique_no', '!=', $excludeVoucherNo);
                    }
                })
                ->sum('amount');

            // Deduct previous JV adjustments for this DO
            $alreadyJvAdjusted = (float) \App\Models\JournalVoucherDetail::where('delivery_order_id', $do->id)
                ->whereHas('journalVoucher', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('debit_amount');

            $totalDeductions = $alreadyRefunded + $alreadyJvAdjusted;
            $firstRate = (float) ($do->delivery_order_data->first()->rate ?? 1);
            $refundedQty = $firstRate > 0 ? ($totalDeductions / $firstRate) : 0;

            $totalRemainingAmount = max(0, $totalRemainingAmount - $totalDeductions);
            $totalRemainingQty = max(0, $totalRemainingQty - $refundedQty);

            if ($totalRemainingQty > 0 || $totalRemainingAmount > 0.01) {
                $statusText = $do->do_status === 'closed' ? ' [Closed]' : ($do->am_approval_status === 'pending' ? ' [Pending Approval]' : '');
                $dos[] = [
                    'id' => $do->id,
                    'reference_no' => $do->reference_no ?: ('DO #' . $do->id),
                    'do_status' => $do->do_status,
                    'am_approval_status' => $do->am_approval_status,
                    'do_qty' => $totalDoQty,
                    'dc_qty' => $totalDcQty,
                    'remaining_qty' => round($totalRemainingQty, 2),
                    'remaining_amount' => round($totalRemainingAmount, 2),
                    'formatted_remaining' => number_format($totalRemainingAmount, 2),
                    'text' => ($do->reference_no ?: ('DO #' . $do->id)) . " (Rem: " . round($totalRemainingQty, 2) . " Qty | Rs. " . number_format($totalRemainingAmount, 2) . "){$statusText}"
                ];
            }
        }

        // Calculate SO direct returnable advance (taking into account approved RV advance, DO consumption, JV deductions, and previous DPV refunds)
        $soTotalQty = 0;
        foreach ($salesOrder->sales_order_data as $soItem) {
            $soTotalQty += (float) $soItem->qty;
        }

        $firstSoRate = (float) ($salesOrder->sales_order_data->first()->rate ?? 1);
        $soRemainingAmount = $this->getSalesOrderRemainingAdvance($salesOrder, $excludeVoucherNo);
        $soRemainingQty = $firstSoRate > 0 ? round($soRemainingAmount / $firstSoRate, 2) : 0;

        return response()->json([
            'success' => true,
            'so_id' => $salesOrder->id,
            'so_reference_no' => $salesOrder->reference_no,
            'so_status' => $salesOrder->status,
            'so_total_qty' => $soTotalQty,
            'so_remaining_qty' => round($soRemainingQty, 2),
            'so_remaining_amount' => round($soRemainingAmount, 2),
            'so_formatted_remaining' => number_format($soRemainingAmount, 2),
            'has_dos' => count($dos) > 0,
            'delivery_orders' => $dos
        ]);
    }

    public function getDoStatsForPayment(Request $request, $doId)
    {
        $do = DeliveryOrder::with(['delivery_order_data.item'])->findOrFail($doId);

        if ($do->do_status === 'active' && delivery_order_has_loading_program($do->id)) {
            return response()->json([
                'success' => false,
                'message' => "Delivery Order ({$do->reference_no}) is active and has an associated Loading Program. Payment cannot be refunded unless DO is closed."
            ], 422);
        }

        $excludeVoucherNo = $request->query('exclude_voucher_no');

        $totalDoQty = 0;
        $totalDcQty = 0;
        $totalRemainingQty = 0;
        $totalRemainingAmount = 0;
        $totalDispatchedValue = 0;

        foreach ($do->delivery_order_data as $itemData) {
            $iDoQty = (float) $itemData->qty;
            $iDcQty = (float) DeliveryChallanData::where('do_data_id', $itemData->id)
                ->whereHas('deliveryChallan', function ($q) {
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('qty');

            $iRemQty = max(0, $iDoQty - $iDcQty);
            $iRate = (float) $itemData->rate;
            $iRemAmt = $iRemQty * $iRate;

            $totalDoQty += $iDoQty;
            $totalDcQty += $iDcQty;
            $totalRemainingQty += $iRemQty;
            $totalRemainingAmount += $iRemAmt;
            $totalDispatchedValue += ($iDcQty * $iRate);
        }

        // Attached cash advance to this DO from approved RVs
        $attachedRvAmount = (float) DB::table('delivery_order_receipt_voucher as dorv')
            ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
            ->whereNull('rv.deleted_at')
            ->where('rv.am_approval_status', 'approved')
            ->where('dorv.delivery_order_id', $do->id)
            ->sum('dorv.amount');

        if ($attachedRvAmount > 0) {
            $remainingAttachedAdvance = max(0, $attachedRvAmount - $totalDispatchedValue);
            // Returnable cash advance takes precedence if higher than discrete bag rounding
            $totalRemainingAmount = max($totalRemainingAmount, $remainingAttachedAdvance);
        }

        // Deduct previous DPV refunds for this DO
        $alreadyRefunded = (float) PaymentVoucherData::where('delivery_order_id', $do->id)
            ->whereHas('paymentVoucher', function ($q) use ($excludeVoucherNo) {
                $q->whereNull('deleted_at');
                $q->where('am_approval_status', '!=', 'rejected');
                if ($excludeVoucherNo) {
                    $q->where('unique_no', '!=', $excludeVoucherNo);
                }
            })
            ->sum('amount');

        // Deduct previous JV adjustments for this DO
        $alreadyJvAdjusted = (float) \App\Models\JournalVoucherDetail::where('delivery_order_id', $do->id)
            ->whereHas('journalVoucher', function ($q) {
                $q->whereNull('deleted_at');
                $q->where('am_approval_status', '!=', 'rejected');
            })
            ->sum('debit_amount');

        $totalDeductions = $alreadyRefunded + $alreadyJvAdjusted;
        $firstRate = (float) ($do->delivery_order_data->first()->rate ?? 1);
        $refundedQty = $firstRate > 0 ? ($totalDeductions / $firstRate) : 0;

        $totalRemainingAmount = max(0, $totalRemainingAmount - $totalDeductions);
        $totalRemainingQty = max(0, $totalRemainingQty - $refundedQty);

        return response()->json([
            'success' => true,
            'id' => $do->id,
            'reference_no' => $do->reference_no ?: ('DO #' . $do->id),
            'do_status' => $do->do_status,
            'do_qty' => $totalDoQty,
            'dc_qty' => $totalDcQty,
            'remaining_qty' => round($totalRemainingQty, 2),
            'remaining_amount' => round($totalRemainingAmount, 2),
            'formatted_remaining' => number_format($totalRemainingAmount, 2)
        ]);
    }

    public function getCustomerBankAccounts($accountId)
    {
        $customer = $this->resolveCustomerFromAccountId($accountId);
        if (!$customer) {
            return response()->json(['success' => true, 'bank_accounts' => []]);
        }

        $customer->load(['companyBankDetails', 'ownerBankDetails']);
        $bankAccounts = collect();

        foreach ($customer->companyBankDetails as $bank) {
            $bankAccounts->push([
                'id' => $bank->id,
                'type' => 'company',
                'title' => $customer->name ?? '',
                'account_title' => $bank->account_title ?? '',
                'account_number' => $bank->account_number ?? '',
                'bank_name' => $bank->bank_name ?? '',
                'branch_name' => $bank->branch_name ?? '',
                'branch_code' => $bank->branch_code ?? '',
            ]);
        }

        foreach ($customer->ownerBankDetails as $bank) {
            $bankAccounts->push([
                'id' => $bank->id,
                'type' => 'owner',
                'title' => $customer->name ?? '',
                'account_title' => $bank->account_title ?? '',
                'account_number' => $bank->account_number ?? '',
                'bank_name' => $bank->bank_name ?? '',
                'branch_name' => $bank->branch_name ?? '',
                'branch_code' => $bank->branch_code ?? '',
            ]);
        }

        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'bank_accounts' => $bankAccounts
        ]);
    }

    public function direct_payment_voucher_sale_store(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'customer_account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'bank_account_id' => 'nullable',
            'bank_account_type' => 'nullable|string',
            'row_type.*' => 'required|in:excess_amount,delivery_order,journal_voucher',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        if (empty($request->row_type)) {
            return response()->json(['message' => 'Please add at least one line item.'], 422);
        }

        DB::beginTransaction();
        try {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
            $datePrefix = $prefix . '-' . date('m-d-Y', strtotime($request->pv_date)) . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id,
                'request_account_id' => $request->customer_account_id,
                'bank_account_id' => $request->bank_account_id ?: null,
                'bank_account_type' => $request->bank_account_type ?: null,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
                'total_amount' => 0,
                'is_direct' => 1,
                'module_type' => 'dpv_for_sale',
            ]);

            $totalAmount = 0;

            foreach ($request->row_type as $index => $rowType) {
                $amount = (float) $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = (float) ($request->tax_amount[$index] ?? 0);
                $netAmount = (float) ($request->net_amount[$index] ?? ($amount + $taxAmount));
                $desc = $request->description[$index] ?? '';

                if ($rowType === 'journal_voucher') {
                    $jvDetailId = $request->journal_voucher_detail_id[$index] ?? null;
                    if (!$jvDetailId) {
                        throw new \Exception("Please select a Journal Voucher for row #" . ($index + 1));
                    }

                    $jvDetail = \App\Models\JournalVoucherDetail::with('journalVoucher')->lockForUpdate()->find($jvDetailId);
                    if (!$jvDetail || !$jvDetail->journalVoucher) {
                        throw new \Exception("Journal Voucher record not found for row #" . ($index + 1));
                    }

                    if ($jvDetail->journalVoucher->am_approval_status !== 'approved') {
                        throw new \Exception("Row #" . ($index + 1) . ": Journal Voucher ({$jvDetail->journalVoucher->jv_no}) must be approved to issue payment.");
                    }

                    $creditAmt = (float) $jvDetail->credit_amount;
                    $settlementSpent = (float) DB::table('settlement_adjustments')
                        ->where('reference_type', 'journal_voucher')
                        ->where('reference_id', $jvDetail->journal_voucher_id)
                        ->sum('amount');

                    $alreadyRefunded = (float) PaymentVoucherData::where(function ($q) use ($jvDetail) {
                        $q->where('journal_voucher_detail_id', $jvDetail->id)
                          ->orWhere(function ($sq) use ($jvDetail) {
                              $sq->where('journal_voucher_id', $jvDetail->journal_voucher_id)
                                 ->where('account_id', $jvDetail->acc_id);
                          })
                          ->orWhere(function ($sq) use ($jvDetail) {
                              $sq->where('reference_type', 'journal_voucher')
                                 ->where('reference_id', $jvDetail->id);
                          });
                    })
                    ->whereHas('paymentVoucher', function ($q) {
                        $q->whereNull('deleted_at')
                          ->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('net_amount');

                    $maxAllowed = max(0, $creditAmt - max($settlementSpent, $alreadyRefunded));

                    if ($netAmount > $maxAllowed + 0.01) {
                        throw new \Exception("Row #" . ($index + 1) . ": Amount cannot exceed the remaining balance of Journal Voucher ({$jvDetail->journalVoucher->jv_no}) which is Rs. " . number_format($maxAllowed, 2));
                    }

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $paymentVoucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Refund against JV #{$jvDetail->journalVoucher->jv_no}",
                        'reference_type' => 'journal_voucher',
                        'reference_id' => $jvDetail->id,
                        'journal_voucher_id' => $jvDetail->journal_voucher_id,
                        'journal_voucher_detail_id' => $jvDetail->id,
                        'customer_advance_id' => null,
                        'sales_order_id' => null,
                        'delivery_order_id' => null,
                    ]);

                    DB::table('settlement_adjustments')->insert([
                        'reference_type' => 'journal_voucher',
                        'reference_id' => $jvDetail->journal_voucher_id,
                        'voucher_no' => $uniqueNo,
                        'amount' => $netAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($rowType === 'excess_amount') {
                    $advId = $request->customer_advance_id[$index] ?? null;
                    if (!$advId) {
                        throw new \Exception("Please select an Excess Payment voucher for row #" . ($index + 1));
                    }

                    $adv = CustomerAdvance::lockForUpdate()->find($advId);
                    if (!$adv) {
                        throw new \Exception("Excess Payment voucher not found for row #" . ($index + 1));
                    }

                    if ($netAmount > (float) $adv->remaining_amount + 0.001) {
                        throw new \Exception("Amount cannot exceed the remaining balance of Excess Payment ({$adv->voucher_no}) which is Rs. " . number_format($adv->remaining_amount, 2));
                    }

                    $adv->used_amount += $netAmount;
                    $adv->remaining_amount -= $netAmount;
                    $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                    $adv->save();

                    CustomerAdvanceAdjustment::create([
                        'customer_advance_id' => $adv->id,
                        'voucher_no' => $uniqueNo,
                        'amount' => $netAmount,
                    ]);

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $paymentVoucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Refund of Excess Payment ({$adv->voucher_no})",
                        'reference_type' => 'customer_advance',
                        'reference_id' => $adv->id,
                        'customer_advance_id' => $adv->id,
                        'sales_order_id' => null,
                        'delivery_order_id' => null,
                    ]);
                } else { // delivery_order
                    $soId = $request->sales_order_id[$index] ?? null;
                    if (!$soId) {
                        throw new \Exception("Please select a Sales Order for row #" . ($index + 1));
                    }
                    $doId = !empty($request->delivery_order_id[$index]) ? $request->delivery_order_id[$index] : null;

                    $maxAllowed = 0;
                    if ($doId) {
                        $do = DeliveryOrder::with(['delivery_order_data'])->findOrFail($doId);

                        if ($do->do_status === 'active' && delivery_order_has_loading_program($do->id)) {
                            throw new \Exception("Row #" . ($index + 1) . ": Payment cannot be issued for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to refund.");
                        }

                        $totalDispatchedValue = 0;
                        foreach ($do->delivery_order_data as $dod) {
                            $dcQty = (float) DeliveryChallanData::where('do_data_id', $dod->id)
                                ->whereHas('deliveryChallan', function ($q) {
                                    $q->where('am_approval_status', '!=', 'rejected');
                                })
                                ->sum('qty');
                            $maxAllowed += max(0, (float)$dod->qty - $dcQty) * (float)$dod->rate;
                            $totalDispatchedValue += $dcQty * (float)$dod->rate;
                        }

                        $attachedRvAmount = (float) DB::table('delivery_order_receipt_voucher')
                            ->where('delivery_order_id', $do->id)
                            ->sum('amount');
                        if ($attachedRvAmount > 0) {
                            $remAttachedAdvance = max(0, $attachedRvAmount - $totalDispatchedValue);
                            $maxAllowed = max($maxAllowed, $remAttachedAdvance);
                        }

                        $alreadyRefunded = (float) PaymentVoucherData::where('delivery_order_id', $do->id)
                            ->whereHas('paymentVoucher', function ($q) {
                                $q->whereNull('deleted_at');
                            })
                            ->sum('amount');
                        $maxAllowed = max(0, $maxAllowed - $alreadyRefunded);

                        $refType = 'delivery_order';
                        $refId = $do->id;
                    } else {
                        $so = SalesOrder::with(['sales_order_data'])->findOrFail($soId);
                        $maxAllowed = $this->getSalesOrderRemainingAdvance($so);
                        $refType = 'sales_order';
                        $refId = $so->id;
                    }

                    if ($netAmount > $maxAllowed + 0.01) {
                        $label = $doId ? "Delivery Order" : "Sales Order";
                        throw new \Exception("Row #" . ($index + 1) . ": Amount cannot exceed the remaining returnable amount of {$label} which is Rs. " . number_format($maxAllowed, 2));
                    }

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $paymentVoucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Sale Return for " . ($doId ? "DO #$doId" : "SO #$soId"),
                        'reference_type' => $refType,
                        'reference_id' => $refId,
                        'customer_advance_id' => null,
                        'sales_order_id' => $soId,
                        'delivery_order_id' => $doId,
                    ]);

                    // Note: do_status is intentionally kept active per user requirement; lock is enforced at Second Weighbridge

                    // DO and SO statuses are managed through their standard dispatch/fulfillment flows
                }

                $totalAmount += $netAmount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();

            DB::commit();

            \App\Services\NotificationService::sendPaymentVoucherNotification($paymentVoucher);

            return response()->json([
                'success' => 'Direct Payment Voucher (Sale) created successfully!',
                'redirect' => route('payment-voucher.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update_direct_sale(Request $request, PaymentVoucher $payment_voucher)
    {
        if (!$payment_voucher->is_direct || $payment_voucher->module_type !== 'dpv_for_sale') {
            abort(404);
        }

        $status = strtolower($payment_voucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => "Cannot edit a Payment Voucher that has been {$status}."], 422);
        }

        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'customer_account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'bank_account_id' => 'nullable',
            'bank_account_type' => 'nullable|string',
            'row_type.*' => 'required|in:excess_amount,delivery_order,journal_voucher',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        if (empty($request->row_type)) {
            return response()->json(['message' => 'Please add at least one line item.'], 422);
        }

        DB::beginTransaction();
        try {
            $payment_voucher->update([
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id,
                'request_account_id' => $request->customer_account_id,
                'bank_account_id' => $request->bank_account_id ?: null,
                'bank_account_type' => $request->bank_account_type ?: null,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
            ]);

            // Revert previous CustomerAdvanceAdjustment records
            $prevAdjustments = CustomerAdvanceAdjustment::where('voucher_no', $payment_voucher->unique_no)->get();
            foreach ($prevAdjustments as $adj) {
                $adv = CustomerAdvance::find($adj->customer_advance_id);
                if ($adv) {
                    $adv->used_amount -= $adj->amount;
                    $adv->remaining_amount += $adj->amount;
                    $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                    $adv->save();
                }
                $adj->delete();
            }

            // Revert previous settlement_adjustments for this voucher
            DB::table('settlement_adjustments')
                ->where('voucher_no', $payment_voucher->unique_no)
                ->where('reference_type', 'journal_voucher')
                ->delete();

            // Delete old items
            $payment_voucher->paymentVoucherData()->delete();

            $totalAmount = 0;

            foreach ($request->row_type as $index => $rowType) {
                $amount = (float) $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = (float) ($request->tax_amount[$index] ?? 0);
                $netAmount = (float) ($request->net_amount[$index] ?? ($amount + $taxAmount));
                $desc = $request->description[$index] ?? '';

                if ($rowType === 'journal_voucher') {
                    $jvDetailId = $request->journal_voucher_detail_id[$index] ?? null;
                    if (!$jvDetailId) {
                        throw new \Exception("Please select a Journal Voucher for row #" . ($index + 1));
                    }

                    $jvDetail = \App\Models\JournalVoucherDetail::with('journalVoucher')->lockForUpdate()->find($jvDetailId);
                    if (!$jvDetail || !$jvDetail->journalVoucher) {
                        throw new \Exception("Journal Voucher record not found for row #" . ($index + 1));
                    }

                    if ($jvDetail->journalVoucher->am_approval_status !== 'approved') {
                        throw new \Exception("Row #" . ($index + 1) . ": Journal Voucher ({$jvDetail->journalVoucher->jv_no}) must be approved to issue payment.");
                    }

                    $creditAmt = (float) $jvDetail->credit_amount;
                    $settlementSpent = (float) DB::table('settlement_adjustments')
                        ->where('reference_type', 'journal_voucher')
                        ->where('reference_id', $jvDetail->journal_voucher_id)
                        ->where('voucher_no', '!=', $payment_voucher->unique_no)
                        ->sum('amount');

                    $alreadyRefunded = (float) PaymentVoucherData::where(function ($q) use ($jvDetail) {
                        $q->where('journal_voucher_detail_id', $jvDetail->id)
                          ->orWhere(function ($sq) use ($jvDetail) {
                              $sq->where('journal_voucher_id', $jvDetail->journal_voucher_id)
                                 ->where('account_id', $jvDetail->acc_id);
                          })
                          ->orWhere(function ($sq) use ($jvDetail) {
                              $sq->where('reference_type', 'journal_voucher')
                                 ->where('reference_id', $jvDetail->id);
                          });
                    })
                    ->where('payment_voucher_id', '!=', $payment_voucher->id)
                    ->whereHas('paymentVoucher', function ($q) {
                        $q->whereNull('deleted_at')
                          ->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('net_amount');

                    $maxAllowed = max(0, $creditAmt - max($settlementSpent, $alreadyRefunded));

                    if ($netAmount > $maxAllowed + 0.01) {
                        throw new \Exception("Row #" . ($index + 1) . ": Amount cannot exceed the remaining balance of Journal Voucher ({$jvDetail->journalVoucher->jv_no}) which is Rs. " . number_format($maxAllowed, 2));
                    }

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $payment_voucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Refund against JV #{$jvDetail->journalVoucher->jv_no}",
                        'reference_type' => 'journal_voucher',
                        'reference_id' => $jvDetail->id,
                        'journal_voucher_id' => $jvDetail->journal_voucher_id,
                        'journal_voucher_detail_id' => $jvDetail->id,
                        'customer_advance_id' => null,
                        'sales_order_id' => null,
                        'delivery_order_id' => null,
                    ]);

                    DB::table('settlement_adjustments')->insert([
                        'reference_type' => 'journal_voucher',
                        'reference_id' => $jvDetail->journal_voucher_id,
                        'voucher_no' => $payment_voucher->unique_no,
                        'amount' => $netAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($rowType === 'excess_amount') {
                    $advId = $request->customer_advance_id[$index] ?? null;
                    if (!$advId) {
                        throw new \Exception("Please select an Excess Payment voucher for row #" . ($index + 1));
                    }

                    $adv = CustomerAdvance::lockForUpdate()->find($advId);
                    if (!$adv) {
                        throw new \Exception("Excess Payment voucher not found for row #" . ($index + 1));
                    }

                    if ($netAmount > (float) $adv->remaining_amount + 0.001) {
                        throw new \Exception("Amount cannot exceed the remaining balance of Excess Payment ({$adv->voucher_no}) which is Rs. " . number_format($adv->remaining_amount, 2));
                    }

                    $adv->used_amount += $netAmount;
                    $adv->remaining_amount -= $netAmount;
                    $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                    $adv->save();

                    CustomerAdvanceAdjustment::create([
                        'customer_advance_id' => $adv->id,
                        'voucher_no' => $payment_voucher->unique_no,
                        'amount' => $netAmount,
                    ]);

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $payment_voucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Refund of Excess Payment ({$adv->voucher_no})",
                        'reference_type' => 'customer_advance',
                        'reference_id' => $adv->id,
                        'customer_advance_id' => $adv->id,
                        'sales_order_id' => null,
                        'delivery_order_id' => null,
                    ]);
                } else { // delivery_order
                    $soId = $request->sales_order_id[$index] ?? null;
                    if (!$soId) {
                        throw new \Exception("Please select a Sales Order for row #" . ($index + 1));
                    }
                    $doId = !empty($request->delivery_order_id[$index]) ? $request->delivery_order_id[$index] : null;

                    $maxAllowed = 0;
                    if ($doId) {
                        $do = DeliveryOrder::with(['delivery_order_data'])->findOrFail($doId);

                        if ($do->do_status === 'active' && delivery_order_has_loading_program($do->id)) {
                            throw new \Exception("Row #" . ($index + 1) . ": Payment cannot be issued for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to refund.");
                        }

                        $totalDispatchedValue = 0;
                        foreach ($do->delivery_order_data as $dod) {
                            $dcQty = (float) DeliveryChallanData::where('do_data_id', $dod->id)
                                ->whereHas('deliveryChallan', function ($q) {
                                    $q->where('am_approval_status', '!=', 'rejected');
                                })
                                ->sum('qty');
                            $maxAllowed += max(0, (float)$dod->qty - $dcQty) * (float)$dod->rate;
                            $totalDispatchedValue += $dcQty * (float)$dod->rate;
                        }

                        $attachedRvAmount = (float) DB::table('delivery_order_receipt_voucher')
                            ->where('delivery_order_id', $do->id)
                            ->sum('amount');
                        if ($attachedRvAmount > 0) {
                            $remAttachedAdvance = max(0, $attachedRvAmount - $totalDispatchedValue);
                            $maxAllowed = max($maxAllowed, $remAttachedAdvance);
                        }

                        $alreadyRefunded = (float) PaymentVoucherData::where('delivery_order_id', $do->id)
                            ->where('payment_voucher_id', '!=', $payment_voucher->id)
                            ->whereHas('paymentVoucher', function ($q) {
                                $q->whereNull('deleted_at');
                            })
                            ->sum('amount');
                        $maxAllowed = max(0, $maxAllowed - $alreadyRefunded);

                        $refType = 'delivery_order';
                        $refId = $do->id;
                    } else {
                        $so = SalesOrder::with(['sales_order_data'])->findOrFail($soId);
                        $maxAllowed = $this->getSalesOrderRemainingAdvance($so, $payment_voucher->unique_no, $payment_voucher->id);
                        $refType = 'sales_order';
                        $refId = $so->id;
                    }

                    if ($netAmount > $maxAllowed + 0.01) {
                        $label = $doId ? "Delivery Order" : "Sales Order";
                        throw new \Exception("Row #" . ($index + 1) . ": Amount cannot exceed the remaining returnable amount of {$label} which is Rs. " . number_format($maxAllowed, 2));
                    }

                    PaymentVoucherData::create([
                        'payment_voucher_id' => $payment_voucher->id,
                        'account_id' => $request->customer_account_id,
                        'amount' => $amount,
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'description' => $desc ?: "Sale Return for " . ($doId ? "DO #$doId" : "SO #$soId"),
                        'reference_type' => $refType,
                        'reference_id' => $refId,
                        'customer_advance_id' => null,
                        'sales_order_id' => $soId,
                        'delivery_order_id' => $doId,
                    ]);

                    // Note: do_status is intentionally kept active per user requirement; lock is enforced at Second Weighbridge

                    // DO and SO statuses are managed through their standard dispatch/fulfillment flows
                }

                $totalAmount += $netAmount;
            }

            $payment_voucher->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => 'Direct Payment Voucher (Sale) updated successfully!',
                'redirect' => route('payment-voucher.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the specified payment voucher.
     */
    public function show($id)
    {
        $paymentVoucher = PaymentVoucher::with([
            'paymentVoucherData.account',
            'paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder',
            'paymentVoucherData.customerAdvance',
            'paymentVoucherData.salesOrder',
            'paymentVoucherData.deliveryOrder',
            'billPaymentVoucherData',
            'account',
            'supplier',
            'requestAccount',
        ])->findOrFail($id);

        $transactions = Transaction::where('transaction_voucher_type_id', 1)->where('voucher_no', $paymentVoucher->unique_no)
            ->get();

        if ($transactions->isEmpty()) {
            $transactions = $paymentVoucher->getPreviewTransactions();
        }

        $bankAccount = $this->resolveVoucherBankAccount($paymentVoucher);

        return view('management.finance.payment_voucher.show', [
            'paymentVoucher' => $paymentVoucher,
            'transactions' => $transactions,
            'bankAccount' => $bankAccount,
        ]);
    }

    public function manageApprovals($id)
    {
        $paymentVoucher = PaymentVoucher::with([
            'paymentVoucherData.account',
            'paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder',
            'paymentVoucherData.customerAdvance',
            'paymentVoucherData.salesOrder',
            'paymentVoucherData.deliveryOrder',
            'billPaymentVoucherData',
            'account',
            'supplier',
            'requestAccount',
        ])->findOrFail($id);

        $transactions = Transaction::where('transaction_voucher_type_id', 1)->where('voucher_no', $paymentVoucher->unique_no)
            ->get();

        if ($transactions->isEmpty()) {
            $transactions = $paymentVoucher->getPreviewTransactions();
        }

        $bankAccount = $this->resolveVoucherBankAccount($paymentVoucher);

        return view('management.finance.payment_voucher.approvalCanvas', [
            'paymentVoucher' => $paymentVoucher,
            'data' => $paymentVoucher,
            'transactions' => $transactions,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Resolve payee bank details across all voucher types and linked party tables.
     */
    private function resolveVoucherBankAccount(PaymentVoucher $paymentVoucher)
    {
        if (!$paymentVoucher->bank_account_id) {
            return null;
        }

        $table_name = $paymentVoucher->requestAccount->table_name ?? null;
        if (!$table_name) {
            $table_name = $paymentVoucher->paymentVoucherData->first()?->account?->table_name ?? null;
        }
        if (!$table_name) {
            $accId = $paymentVoucher->paymentVoucherData->first()?->paymentRequest?->account_id;
            if ($accId) {
                $table_name = Account::find($accId)?->table_name;
            }
        }
        if (!$table_name && $paymentVoucher->supplier_id) {
            $table_name = 'suppliers';
        }
        if (!$table_name && $paymentVoucher->paymentVoucherData->first()?->paymentRequest?->paymentRequestData?->purchaseOrder?->supplier_id) {
            $table_name = 'suppliers';
        }

        $type = $paymentVoucher->bank_account_type ?: 'company';
        $bankAccount = null;

        if ($table_name == 'suppliers') {
            $bankAccount = ($type === 'owner') ? SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id) : SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id);
        } elseif ($table_name == 'brokers') {
            $bankAccount = ($type === 'owner') ? BrokerOwnerBankDetail::find($paymentVoucher->bank_account_id) : BrokerCompanyBankDetail::find($paymentVoucher->bank_account_id);
        } elseif ($table_name == 'vendors') {
            $bankAccount = ($type === 'owner') ? VendorOwnerBankDetail::find($paymentVoucher->bank_account_id) : VendorCompanyBankDetail::find($paymentVoucher->bank_account_id);
        } elseif ($table_name == 'transporters') {
            $bankAccount = ($type === 'owner') ? TransporterOwnerBankDetail::find($paymentVoucher->bank_account_id) : TransporterCompanyBankDetail::find($paymentVoucher->bank_account_id);
        } elseif ($table_name == 'customers') {
            $bankAccount = ($type === 'owner') ? CustomerOwnerBankDetail::find($paymentVoucher->bank_account_id) : CustomerCompanyBankDetail::find($paymentVoucher->bank_account_id);
        }

        // Universal fallback if table_name did not match or find record
        if (!$bankAccount) {
            if ($type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id)
                    ?? BrokerOwnerBankDetail::find($paymentVoucher->bank_account_id)
                    ?? TransporterOwnerBankDetail::find($paymentVoucher->bank_account_id)
                    ?? CustomerOwnerBankDetail::find($paymentVoucher->bank_account_id)
                    ?? VendorOwnerBankDetail::find($paymentVoucher->bank_account_id);
            } else {
                $bankAccount = SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id)
                    ?? BrokerCompanyBankDetail::find($paymentVoucher->bank_account_id)
                    ?? TransporterCompanyBankDetail::find($paymentVoucher->bank_account_id)
                    ?? CustomerCompanyBankDetail::find($paymentVoucher->bank_account_id)
                    ?? VendorCompanyBankDetail::find($paymentVoucher->bank_account_id);
            }
        }

        if (!$bankAccount) {
            $bankAccount = SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id)
                ?? SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id)
                ?? BrokerCompanyBankDetail::find($paymentVoucher->bank_account_id)
                ?? BrokerOwnerBankDetail::find($paymentVoucher->bank_account_id)
                ?? TransporterCompanyBankDetail::find($paymentVoucher->bank_account_id)
                ?? TransporterOwnerBankDetail::find($paymentVoucher->bank_account_id)
                ?? CustomerCompanyBankDetail::find($paymentVoucher->bank_account_id)
                ?? CustomerOwnerBankDetail::find($paymentVoucher->bank_account_id)
                ?? VendorCompanyBankDetail::find($paymentVoucher->bank_account_id)
                ?? VendorOwnerBankDetail::find($paymentVoucher->bank_account_id);
        }

        return $bankAccount;
    }

    /**
     * Generate PV number
     */
    public function generatePvNumber(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'nullable|date',
        ]);

        $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $prefixForAccounts = $request->voucher_type === 'bank_payment_voucher' ? '1-1' : '1-4';

        $accounts = Account::whereHas('parent', function ($query) use ($prefixForAccounts) {
            $query->where('hierarchy_path', $prefixForAccounts);
        })->get();

        $pvDate = $request->pv_date ? date('m-d-Y', strtotime($request->pv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $pvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

        return response()->json([
            'success' => true,
            'pv_number' => $uniqueNo,
            'accounts' => $accounts,
        ]);
    }

    public function getPurchaseBills(string $supplierId)
    {
        $purchase_bill = PurchaseBill::with("grn", "bill_data")
            ->where("supplier_id", $supplierId)
            ->where("am_approval_status", "approved")
            ->get();

        $purchase_bill->each(function ($bill) {
            $remaining_qty = getPaymentVoucherBillBalance($bill);
            $bill->amount = $remaining_qty;
            $bill->debit_amount = getDebitNoteAmountOfBill($bill);
            $bill->return_amount = getPurchaseReturnAmountOfBill($bill);
            $bill->total_bill = totalBill($bill);
            $bill->spent_bill = spentBill($bill);
        });


        return view('management.finance.payment_voucher.purchaseBills', [
            'purchase_bills' => $purchase_bill,
        ]);
    }

    /**
     * Get payment requests for purchase order
     */
    public function getPaymentRequests($supplierId)
    {
        $supplier = Supplier::with(['companyBankDetails', 'ownerBankDetails'])->findOrFail($supplierId);

        $companyBankAccounts = $supplier->companyBankDetails ?? collect();
        $ownerBankAccounts = $supplier->ownerBankDetails ?? collect();

        $bankAccounts = collect();

        if ($companyBankAccounts) {
            foreach ($companyBankAccounts as $bank) {
                $bankAccounts->push([
                    'id' => $bank->id,
                    'type' => 'company',
                    'title' => $bank->supplier->name ?? '',
                    'account_title' => $bank->account_title ?? '',
                    'account_number' => $bank->account_number ?? '',
                    'bank_name' => $bank->bank_name ?? '',
                    'branch_name' => $bank->branch_name ?? '',
                    'branch_code' => $bank->branch_code ?? '',
                ]);
            }
        }

        if ($ownerBankAccounts) {
            foreach ($ownerBankAccounts as $bank) {
                $bankAccounts->push([
                    'id' => $bank->id,
                    'type' => 'owner',
                    'title' => $bank->supplier->name ?? '',
                    'account_title' => $bank->account_title ?? '',
                    'account_number' => $bank->account_number ?? '',
                    'bank_name' => $bank->bank_name ?? '',
                    'branch_name' => $bank->branch_name ?? '',
                    'branch_code' => $bank->branch_code ?? '',
                ]);
            }
        }
        $jvPool = [];
        $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals'])
            ->whereHas('paymentRequestData.purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->whereDoesntHave('paymentVoucherData')
            ->where('status', 'approved')
            ->get()
            ->map(function ($request) use (&$jvPool) {
                return $this->attachGrnAndJvDetails([
                    'id' => $request->id,
                    'supplier_id' => $request->paymentRequestData->purchaseOrder->supplier_id ?? '',
                    'purchaseOrder' => $request->paymentRequestData->purchaseOrder,
                    'truck_no' => $request->paymentRequestData->truck_no ?? '-',
                    'bilty_no' => $request->paymentRequestData->bilty_no ?? '-',
                    'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                        ? $request->paymentRequestData->loading_date->format('Y-m-d')
                        : '-',
                    'no_of_bags' => $request->paymentRequestData->no_of_bags,
                    'loading_weight' => $request->paymentRequestData->loading_weight,
                    'module_type' => $request->paymentRequestData->module_type,
                    'contract_no' => $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A',
                    'amount' => $request->amount,
                    'purpose' => $request->paymentRequestData->notes ?? 'No description',
                    'status' => $request->approval_status,
                    'saudaType' => $request->paymentRequestData->purchaseOrder->saudaType->name ?? '',
                    'type' => ($request->request_type),
                    'request_date' => $request->created_at
                        ? $request->created_at->format('Y-m-d')
                        : '',
                ], $request, $jvPool);
            });

        $paymentRequests = $paymentRequests->filter(function ($item) {
            return ($item['net_amount'] ?? 0) > 0.001;
        })->values();

        return response()->json([
            'success' => true,
            'payment_requests' => $paymentRequests,
            'bank_accounts' => $bankAccounts->values(),
        ]);
    }

    /**
     * Attach GRN number and Journal Voucher (JV) adjustments to payment request data
     */
    private function attachGrnAndJvDetails(array $item, PaymentRequest $request, array &$jvPool = []): array
    {
        $grnNo = $request->paymentRequestData?->grn_no ?? null;
        $grnId = $request->paymentRequestData?->grn_id ?? null;

        $jvAdjustmentAmount = 0;
        $jvId = null;
        $jvNo = null;
        $matchingJvs = [];
        $grossAmount = (float) ($item['amount'] ?? $request->amount ?? 0);
        $needed = $grossAmount;

        if (!empty($grnNo)) {
            $details = JournalVoucherDetail::with('journalVoucher')
                ->where(function ($q) use ($grnNo, $grnId) {
                    $q->where('voucher_no', $grnNo)
                        ->orWhere('voucher_id', $grnNo);
                    if ($grnId) {
                        $q->orWhere('voucher_id', $grnId);
                    }
                })
                ->whereNull('deleted_at')
                ->whereHas('journalVoucher', function ($q) {
                    $q->whereNull('deleted_at')
                        ->where('am_approval_status', 'approved');
                })
                ->orderBy('journal_voucher_id', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $processedJvs = [];
            foreach ($details as $detail) {
                if ($needed <= 0) {
                    break;
                }

                $jv = $detail->journalVoucher;
                if (!$jv || isset($processedJvs[$jv->id])) {
                    continue;
                }
                $processedJvs[$jv->id] = true;

                if (!isset($jvPool[$jv->id])) {
                    $jvLineTotal = (float) JournalVoucherDetail::where('journal_voucher_id', $jv->id)
                        ->where(function ($q) use ($grnNo, $grnId) {
                            $q->where('voucher_no', $grnNo)
                                ->orWhere('voucher_id', $grnNo);
                            if ($grnId) {
                                $q->orWhere('voucher_id', $grnId);
                            }
                        })
                        ->whereNull('deleted_at')
                        ->sum(DB::raw('CASE WHEN debit_amount > 0 THEN debit_amount ELSE credit_amount END'));

                    $spent = (float) DB::table('settlement_adjustments')
                        ->where('reference_type', 'journal_voucher')
                        ->where('reference_id', $jv->id)
                        ->sum('amount');

                    $jvPool[$jv->id] = max(0, $jvLineTotal - $spent);
                }

                $available = $jvPool[$jv->id];

                if ($available > 0) {
                    $allocated = min($needed, $available);
                    if ($allocated > 0) {
                        $matchingJvs[] = [
                            'jv_id' => $jv->id,
                            'jv_no' => $jv->jv_no,
                            'allocated' => $allocated,
                        ];
                        $jvAdjustmentAmount += $allocated;
                        $jvPool[$jv->id] -= $allocated;
                        $needed -= $allocated;
                    }
                }
            }

            if (!empty($matchingJvs)) {
                $jvId = $matchingJvs[0]['jv_id'];
                $jvNo = implode(', ', array_unique(array_column($matchingJvs, 'jv_no')));
            }
        }

        $netAmount = max(0, $grossAmount - $jvAdjustmentAmount);

        $item['grn_no'] = $grnNo;
        $item['jv_id'] = $jvId;
        $item['jv_no'] = $jvNo;
        $item['jv_adjustment_amount'] = round($jvAdjustmentAmount, 2);
        $item['net_amount'] = round($netAmount, 2);
        $item['matching_jvs'] = $matchingJvs;

        return $item;
    }

    /**
     * Get payment requests for account
     */
    public function getAccountPaymentRequests($accountId)
    {
        $account = Account::findOrFail($accountId);

        $tableName = $account->table_name;

        $bankAccounts = collect();
        $paymentRequests = collect();
        $modelId = null;
        $jvPool = [];

        if ($tableName === 'suppliers') {

            $supplier = Supplier::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($supplier) {
                $modelId = $supplier->id;
                $companyBankAccounts = $supplier->companyBankDetails ?? collect();
                $ownerBankAccounts = $supplier->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->supplier->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->supplier->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $request->paymentRequestData->purchaseOrder->supplier_id ?? '',
                            'purchaseOrder' => $request->paymentRequestData->purchaseOrder,
                            'truck_no' => $request->paymentRequestData->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : '-',
                            'no_of_bags' => $request->paymentRequestData->no_of_bags,
                            'loading_weight' => $request->paymentRequestData->loading_weight,
                            'module_type' => $request->delivery_challan_id ? 'sale_order' : ($request->paymentRequestData->module_type ?? 'purchase_order'),
                            'contract_no' => $request->deliveryChallan ? $request->deliveryChallan->dc_no : ($request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A'),
                            'amount' => $request->amount,
                            'purpose' => $request->paymentRequestData->notes ?? 'No description',
                            'status' => $request->approval_status,
                            'saudaType' => $request->paymentRequestData->purchaseOrder->saudaType->name ?? '',
                            'type' => ($request->request_type ?? null),
                            'file' => ($request->paymentRequestData->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });

            }
        } elseif ($tableName === 'brokers') {
            $broker = Broker::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($broker) {
                $modelId = $broker->id;
                $companyBankAccounts = $broker->companyBankDetails ?? collect();
                $ownerBankAccounts = $broker->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount,
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Broker commission for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status,
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type),
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } elseif ($tableName === 'vendors') {
            $broker = Vendor::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($broker) {
                $modelId = $broker->id;
                $companyBankAccounts = $broker->companyBankDetails ?? collect();
                $ownerBankAccounts = $broker->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po ?? null,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $request->paymentRequestData?->arrivalTicket?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $request->paymentRequestData?->arrivalTicket?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount ?? '',
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Labour for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status ?? '',
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type) ?? '',
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } elseif ($tableName === 'transporters') {
            $transporter = Transporter::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($transporter) {
                $modelId = $transporter->id;
                $companyBankAccounts = $transporter->companyBankDetails ?? collect();
                $ownerBankAccounts = $transporter->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->transporter->name ?? $transporter->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->transporter->name ?? $transporter->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po ?? null,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount ?? '',
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Freight for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status ?? '',
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type) ?? '',
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } else {
            $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                ->where('account_id', $accountId)
                ->whereDoesntHave('paymentVoucherData')
                ->where('status', 'approved')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($request) use (&$jvPool) {
                    $dc = $request->deliveryChallan;
                    $po = $request->paymentRequestData?->purchaseOrder;
                    $firstDcData = $dc?->delivery_challan_data?->first();

                    return $this->attachGrnAndJvDetails([
                        'id' => $request->id,
                        'supplier_id' => $po->supplier_id ?? '',
                        'purchaseOrder' => $po ?? null,
                        'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                        'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                        'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                            ? $request->paymentRequestData->loading_date->format('Y-m-d')
                            : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                        'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                        'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                        'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                        'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                        'amount' => $request->amount ?? '',
                        'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Commission for DC: {$dc->dc_no}" : 'No description'),
                        'status' => $request->approval_status ?? '',
                        'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                        'type' => ($request->request_type) ?? '',
                        'file' => ($request->paymentRequestData?->attachment ?? null),
                        'request_date' => $request->created_at
                            ? $request->created_at->format('Y-m-d')
                            : '',
                    ], $request, $jvPool);
                });
        }

        // Filter out payment requests that are fully adjusted by JV (net_amount <= 0)
        // so that they do not appear in Payment Voucher creation
        $paymentRequests = $paymentRequests->filter(function ($item) {
            return ($item['net_amount'] ?? 0) > 0.001;
        })->values();

        return response()->json([
            'success' => true,
            'payment_requests' => $paymentRequests,
            'model_id' => $modelId,
            'bank_accounts' => $bankAccounts->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storebk(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'model_id' => 'required',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'model_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());
            $firstRequest = PaymentRequest::with('paymentRequestData.purchaseOrder')
                ->find($request->payment_requests[0]);

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => $request->request_account_id,
                'model_id' => $request->model_id,
                'module_id' => $firstRequest->paymentRequestData->purchase_order_id ?? null,
                'module_type' => 'raw_material_purchase',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $allPaymentAgainst = [];
            $allReferenceNos = [];

            // Build bank remark base (before loop, needs total — so collect first)
            $perRequestData = [];

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                $ticketNo = ($paymentRequest->paymentRequestData->module_type == 'ticket' || $paymentRequest->paymentRequestData->module_type == 'freight_payment')
                    ? $paymentRequest->paymentRequestData->arrivalTicket->unique_no
                    : $paymentRequest->paymentRequestData->purchaseTicket->unique_no;

                $truckNo = $paymentRequest->paymentRequestData->truck_no;
                $biltyNo = $paymentRequest->paymentRequestData->bilty_no;
                $paymentRequestDataId = $paymentRequest->paymentRequestData->id;

                $allPaymentAgainst[] = "$ticketNo-$paymentRequestDataId";
                $allReferenceNos[] = "$truckNo/$biltyNo";

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;

                // Store per-request data for individual supplier debit transactions
                $perRequestData[] = [
                    'amount' => $paymentRequest->amount,
                    'grn_no' => $paymentRequest->paymentRequestData->grn_no,
                    'request_type' => $paymentRequest->request_type,
                    'purchase_ticket_id' => $paymentRequest->paymentRequestData->purchase_ticket_id,
                    'purchase_order_id' => $paymentRequest->paymentRequestData->purchase_order_id,
                    'arrival_ticket_id' => $paymentRequest->paymentRequestData->arrival_ticket_id,
                    'arrival_ticket_no' => $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? '',
                    'purchase_ticket_no' => $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? '',
                    'purchase_order_no' => $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A',

                    // Conditional truck_no and bilty_no based on which ticket exists
                    'truck_no' => $paymentRequest->paymentRequestData->purchase_ticket_id
                        ? ($paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A')
                        : ($paymentRequest->paymentRequestData->arrival_ticket_id
                            ? ($paymentRequest->paymentRequestData->arrivalTicket->truck_no ?? 'N/A')
                            : 'N/A'),

                    'bilty_no' => $paymentRequest->paymentRequestData->purchase_ticket_id
                        ? ($paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A')
                        : ($paymentRequest->paymentRequestData->arrival_ticket_id
                            ? ($paymentRequest->paymentRequestData->arrivalTicket->bilty_no ?? 'N/A')
                            : 'N/A'),

                    'request_type' => $paymentRequest->request_type,
                    'ticketNo' => $ticketNo,
                    'paymentRequestDataId' => $paymentRequestDataId,
                    'truckNo' => $truckNo,
                    'biltyNo' => $biltyNo,
                    'notes' => $paymentRequest->paymentRequestData->notes ?? '',
                ];
            }


            dd($perRequestData);

            // Build remark for bank (total amount)
            $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made.";
            if ($bankName) {
                $bankRemarks .= " against bank '{$bankName}'";
            }
            if ($accountNumber) {
                $bankRemarks .= " with account number '{$accountNumber}'";
            }
            $bankRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash '
                . count($perRequestData) . ' Bill';

            $paymentAgainstStr = implode(', ', $allPaymentAgainst);
            $referenceNosStr = implode(', ', $allReferenceNos);

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'model_id' => 'required',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'model_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        $requestedIds = $request->payment_requests ?? [];
        $sortedRequests = PaymentRequest::with(['paymentRequestData.purchaseOrder', 'deliveryChallan.delivery_challan_data', 'paymentVoucherData'])
            ->whereIn('id', $requestedIds)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($sortedRequests->isEmpty()) {
            return response()->json([
                'error' => 'No valid payment requests found.',
                'message' => 'No valid payment requests found.',
                'errors' => ['payment_requests' => ['No valid payment requests found.']]
            ], 422);
        }

        // 1. Concurrency Check: Ensure no selected PR already has a Payment Voucher created concurrently
        foreach ($sortedRequests as $paymentRequest) {
            if ($paymentRequest->paymentVoucherData()->exists()) {
                $errorMsg = "Payment Request (PR# {$paymentRequest->id}) has already been paid in another Payment Voucher. Please refresh the page.";
                return response()->json([
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => ['payment_requests' => [$errorMsg]]
                ], 422);
            }
        }

        // 2. Concurrency Check: Live simulation of JV adjustments to stop fully adjusted PRs from being processed
        $simJvPool = [];
        foreach ($sortedRequests as $paymentRequest) {
            $simResult = $this->attachGrnAndJvDetails(['amount' => $paymentRequest->amount], $paymentRequest, $simJvPool);
            if (($simResult['net_amount'] ?? 0) <= 0.001 && ($simResult['jv_adjustment_amount'] ?? 0) > 0) {
                $jvText = !empty($simResult['jv_no']) ? "JV# {$simResult['jv_no']}" : "Journal Voucher";
                $grnText = !empty($simResult['grn_no']) ? " (GRN# {$simResult['grn_no']})" : "";
                $errorMsg = "Selected Payment Request (PR# {$paymentRequest->id}{$grnText}) is already fully adjusted via {$jvText}. A Payment Voucher cannot be created for it. Please refresh the page.";
                return response()->json([
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => ['payment_requests' => [$errorMsg]]
                ], 422);
            }
        }

        $paymentVoucher = DB::transaction(function () use ($request, $sortedRequests) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $firstRequest = $sortedRequests->first();

            $requestAccount = Account::find($request->request_account_id);
            $tableName = $requestAccount?->table_name ?? null;

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($tableName == 'suppliers') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'brokers') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = BrokerCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = BrokerOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'vendors') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = VendorCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = VendorOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'transporters') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = TransporterCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = TransporterOwnerBankDetail::find($request->bank_account_id);
                }
            } else {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id)
                        ?? TransporterCompanyBankDetail::find($request->bank_account_id)
                        ?? VendorCompanyBankDetail::find($request->bank_account_id)
                        ?? BrokerCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id)
                        ?? TransporterOwnerBankDetail::find($request->bank_account_id)
                        ?? VendorOwnerBankDetail::find($request->bank_account_id)
                        ?? BrokerOwnerBankDetail::find($request->bank_account_id);
                }
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $moduleId = $firstRequest?->delivery_challan_id ?? ($firstRequest?->paymentRequestData?->purchase_order_id ?? null);
            $moduleType = $firstRequest?->delivery_challan_id ? 'delivery_challan' : 'raw_material_purchase';

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => $request->request_account_id,
                'model_id' => $request->model_id,
                'module_id' => $moduleId,
                'module_type' => $moduleType,
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $allPaymentAgainst = [];
            $allReferenceNos = [];
            $perRequestData = [];
            $jvPool = [];

            foreach ($sortedRequests as $paymentRequest) {
                $requestId = $paymentRequest->id;
                $dc = $paymentRequest->deliveryChallan;
                $ticketDetails = '';

                if ($dc) {
                    $ticketNo = $dc->dc_no;
                    $firstDcData = $dc->delivery_challan_data->first();
                    $truckNo = $paymentRequest->paymentRequestData->truck_no ?? $firstDcData?->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->bilty_no ?? $firstDcData?->bilty_no ?? 'N/A';
                    $ticketDetails = "DC# {$dc->dc_no} VEH# {$truckNo} Bilty# {$biltyNo}";
                } elseif ($paymentRequest->paymentRequestData->purchase_ticket_id) {
                    $ticketNo = $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? 'N/A';
                    $truckNo = $paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';
                    $ticketDetails = "Purchase Ticket #" . $ticketNo . " ";
                    $ticketDetails .= "VEH# " . $truckNo . " ";
                    $ticketDetails .= "Bilty# " . $biltyNo . " ";
                    $ticketDetails .= "PO# " . ($paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A');
                } elseif ($paymentRequest->paymentRequestData->arrival_ticket_id) {
                    $ticketNo = $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? 'N/A';
                    $truckNo = $paymentRequest->paymentRequestData->arrivalTicket->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->arrivalTicket->bilty_no ?? 'N/A';
                    $ticketDetails = "Arrival Ticket # " . $ticketNo . " ";
                    $ticketDetails .= "VEH# " . $truckNo . " ";
                    $ticketDetails .= "Bilty No: " . $biltyNo . " ";
                    $ticketDetails .= "PO# " . ($paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A') . " ";
                    $ticketDetails .= "GRN# " . ($paymentRequest->paymentRequestData->grn_no ?? 'N/A');
                } else {
                    $ticketNo = $paymentRequest->request_no ?? 'PR';
                    $truckNo = $paymentRequest->paymentRequestData->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->bilty_no ?? 'N/A';
                    $ticketDetails = "PR# {$paymentRequest->id} VEH# {$truckNo} Bilty# {$biltyNo}";
                }

                $paymentRequestDataId = $paymentRequest->paymentRequestData->id ?? $paymentRequest->id;

                $allPaymentAgainst[] = "$ticketNo-$paymentRequestDataId";
                $allReferenceNos[] = "$truckNo/$biltyNo";

                // Check for GRN and JV adjustments
                $grnNo = $paymentRequest->paymentRequestData?->grn_no ?? null;
                $grnId = $paymentRequest->paymentRequestData?->grn_id ?? null;

                $appliedAdj = 0;
                $matchingJvNos = [];
                $jvDeductions = [];

                if (!empty($grnNo)) {
                    $details = JournalVoucherDetail::with('journalVoucher')
                        ->where(function ($q) use ($grnNo, $grnId) {
                            $q->where('voucher_no', $grnNo)
                                ->orWhere('voucher_id', $grnNo);
                            if ($grnId) {
                                $q->orWhere('voucher_id', $grnId);
                            }
                        })
                        ->whereNull('deleted_at')
                        ->whereHas('journalVoucher', function ($q) {
                            $q->whereNull('deleted_at')
                                ->where('am_approval_status', 'approved');
                        })
                        ->orderBy('journal_voucher_id', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    // Automatically check and adjust live approved JVs up to PR amount
                    $remainingToAdjust = (float) $paymentRequest->amount;

                    $processedJvsThisRequest = [];
                    foreach ($details as $detail) {
                        if ($remainingToAdjust <= 0) {
                            break;
                        }

                        $jv = $detail->journalVoucher;
                        if (!$jv || isset($processedJvsThisRequest[$jv->id])) {
                            continue;
                        }
                        $processedJvsThisRequest[$jv->id] = true;

                        if (!isset($jvPool[$jv->id])) {
                            $jvLineTotal = (float) JournalVoucherDetail::where('journal_voucher_id', $jv->id)
                                ->where(function ($q) use ($grnNo, $grnId) {
                                    $q->where('voucher_no', $grnNo)
                                        ->orWhere('voucher_id', $grnNo);
                                    if ($grnId) {
                                        $q->orWhere('voucher_id', $grnId);
                                    }
                                })
                                ->whereNull('deleted_at')
                                ->sum(DB::raw('CASE WHEN debit_amount > 0 THEN debit_amount ELSE credit_amount END'));

                            $spent = (float) DB::table('settlement_adjustments')
                                ->where('reference_type', 'journal_voucher')
                                ->where('reference_id', $jv->id)
                                ->sum('amount');

                            $jvPool[$jv->id] = max(0, $jvLineTotal - $spent);
                        }

                        $available = $jvPool[$jv->id];

                        if ($available > 0) {
                            $deduct = min($remainingToAdjust, $available);
                            if ($deduct > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $jv->id,
                                    'voucher_no' => $uniqueNo,
                                    'amount' => $deduct,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $jvPool[$jv->id] -= $deduct;
                                $appliedAdj += $deduct;
                                $remainingToAdjust -= $deduct;
                                $matchingJvNos[] = $jv->jv_no;
                                $jvDeductions[] = [
                                    'jv_no' => $jv->jv_no,
                                    'amount' => $deduct,
                                ];
                            }
                        }
                    }
                }

                $jvNarrationParts = [];
                foreach ($jvDeductions as $jd) {
                    $jvNarrationParts[] = "{$jd['jv_no']}: Rs. " . number_format($jd['amount'], 2);
                }
                $jvNarration = !empty($jvNarrationParts) ? implode(', ', $jvNarrationParts) : null;

                $payableAmount = max(0, (float) $paymentRequest->amount - $appliedAdj);

                if ($payableAmount > 0) {
                    PaymentVoucherData::create([
                        'payment_voucher_id' => $paymentVoucher->id,
                        'payment_request_id' => $requestId,
                        'amount' => $payableAmount,
                        'net_amount' => $payableAmount,
                        'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                    ]);

                    $totalAmount += $payableAmount;
                }

                $perRequestData[] = [
                    'amount' => $payableAmount,
                    'gross_amount' => (float) $paymentRequest->amount,
                    'applied_adj' => $appliedAdj,
                    'jv_nos' => implode(', ', array_unique($matchingJvNos)),
                    'jv_narration' => $jvNarration,
                    'grn_no' => $paymentRequest->paymentRequestData->grn_no ?? null,
                    'request_type' => $paymentRequest->request_type,
                    'purchase_ticket_id' => $paymentRequest->paymentRequestData->purchase_ticket_id ?? null,
                    'purchase_order_id' => $paymentRequest->paymentRequestData->purchase_order_id ?? null,
                    'arrival_ticket_id' => $paymentRequest->paymentRequestData->arrival_ticket_id ?? null,
                    'arrival_ticket_no' => $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? '',
                    'purchase_ticket_no' => $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? '',
                    'purchase_order_no' => $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? ($dc ? $dc->dc_no : 'N/A'),
                    'truck_no' => $truckNo,
                    'bilty_no' => $biltyNo,
                    'ticket_details' => $ticketDetails, // Add this for remarks
                    'ticketNo' => $ticketNo,
                    'paymentRequestDataId' => $paymentRequestDataId,
                    'truckNo' => $truckNo,
                    'biltyNo' => $biltyNo,
                    'notes' => $paymentRequest->paymentRequestData->notes ?? '',
                ];
            }
            // dd($perRequestData);
            // Build bank remark
            $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made.";
            // if ($bankName) {
            //     $bankRemarks .= " against bank '{$bankName}'";
            // }
            // if ($accountNumber) {
            //     $bankRemarks .= " with account number '{$accountNumber}'";
            // }
            $bankRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash ' . count($perRequestData) . ' bills';

            $allJvNarrations = array_filter(array_column($perRequestData, 'jv_narration'));
            $combinedJvNarration = !empty($allJvNarrations) ? implode('; ', array_unique($allJvNarrations)) : null;

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();

            return $paymentVoucher;
        });

        if ($paymentVoucher) {
            \App\Services\NotificationService::sendPaymentVoucherNotification($paymentVoucher);
        }

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }


    public function updateBillPaymentVoucher(Request $request, int $id)
    {
        $payment_voucher = PaymentVoucher::findOrFail($id);

        $status = strtolower($payment_voucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => "Cannot edit a Payment Voucher that has been {$status}."], 422);
        }

        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'supplier_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::find($id);
            $paymentVoucher->update([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'supplier_id' => $request->supplier_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => "-",
                'model_id' => "-",
                'module_id' => null,
                // 'module_type' => $firstRequest->paymentRequestData->module_type ?? 'raw_material_purchase',
                'module_type' => 'bill_payment_voucher',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $paymentVoucher->billPaymentVoucherData()->delete();

            foreach ($request->bill as $index => $bill) {

                $paymentVoucher->billPaymentVoucherData()->create([
                    "payment_voucher_id" => $id,
                    "purchase_bill_id" => $request->purchase_bill_id[$index],
                    "amount" => $request->amounts[$index]
                ]);

                $totalAmount += $request->amounts[$index];
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    public function storeBill(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'supplier_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        $paymentVoucher = DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'supplier_id' => $request->supplier_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => "-",
                'model_id' => "-",
                'module_id' => null,
                // 'module_type' => $firstRequest->paymentRequestData->module_type ?? 'raw_material_purchase',
                'module_type' => 'bill_payment_voucher',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->bill as $index => $bill) {
                $ref_no = $request->purchase_bill_id[$index];

                $amount = number_format($request->amounts[$index], 2);

                $remarks = "A payment of Rs. {$amount} has been made.";
                if ($bankName) {
                    $remarks .= " against bank '{$bankName}'";
                }
                if ($accountNumber) {
                    $remarks .= " with account number '{$accountNumber}'";
                }
                $purchase_bill = PurchaseBill::find($request->purchase_bill_id[$index]);
                $remaining_balance = getPaymentVoucherBillBalance($purchase_bill);
                if ($request->amounts[$index] > $remaining_balance) {
                    DB::rollBack();
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "amounts.$index" => ["Your balance is $remaining_balance, you cannot exceed that balance."]
                    ]);
                }

                $bill_payment_voucher_data = BillPaymentVoucherData::create([
                    "payment_voucher_id" => $paymentVoucher->id,
                    "purchase_bill_id" => $request->purchase_bill_id[$index],
                    "amount" => $request->amounts[$index]
                ]);

                $totalAmount += $request->amounts[$index];
            }


            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();

            return $paymentVoucher;
        });

        if ($paymentVoucher) {
            \App\Services\NotificationService::sendPaymentVoucherNotification($paymentVoucher);
        }

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    public function _store(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'module_id' => 'required|exists:arrival_purchase_orders,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'supplier_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);
        // dd($request->all());
        $paymentVoucher = DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'supplier_id' => $request->supplier_id,
                'module_id' => $request->module_id,
                'module_type' => 'raw_material_purchase',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            $paymentVoucher->autoApprove();

            return $paymentVoucher;
        });

        if ($paymentVoucher) {
            \App\Services\NotificationService::sendPaymentVoucherNotification($paymentVoucher);
        }

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $paymentVoucher = PaymentVoucher::with(['paymentVoucherData.paymentRequest.paymentRequestData'])->findOrFail($id);

        $status = strtolower($paymentVoucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return redirect()->route('payment-voucher.index')->with('error', "Cannot edit a Payment Voucher that has been {$status}.");
        }

        $data = [
            'paymentVoucher' => $paymentVoucher,
            'accounts' => Account::all(),
            'purchaseOrders' => ArrivalPurchaseOrder::with(['product'])->latest()->get(),
            'selectedRequests' => $paymentVoucher->paymentVoucherData->pluck('payment_request_id')->toArray(),
        ];

        return view('management.finance.payment_voucher.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $paymentVoucher = PaymentVoucher::findOrFail($id);

        $status = strtolower($paymentVoucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => "Cannot edit a Payment Voucher that has been {$status}."], 422);
        }

        $request->validate([
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $paymentVoucher) {
            $paymentVoucher->update([
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'request_account_id' => $request->request_account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'remarks' => $request->remarks,
            ]);

            PaymentVoucherData::where('payment_voucher_id', $paymentVoucher->id)->delete();

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => number_format($totalAmount, 2, '.', '')]);
        });

        return response()->json([
            'success' => 'Payment voucher updated successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $paymentVoucher = PaymentVoucher::findOrFail($id);

        $status = strtolower($paymentVoucher->am_approval_status ?? 'pending');
        if (in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => "Cannot delete a Payment Voucher that has been {$status}."], 422);
        }

        // Delete linked settlement adjustments to restore JV available balance
        DB::table('settlement_adjustments')->where('voucher_no', $paymentVoucher->unique_no)->delete();

        // Revert any customer advance adjustments made by this voucher
        $prevAdjustments = CustomerAdvanceAdjustment::where('voucher_no', $paymentVoucher->unique_no)->get();
        foreach ($prevAdjustments as $adj) {
            $adv = CustomerAdvance::find($adj->customer_advance_id);
            if ($adv) {
                $adv->used_amount -= $adj->amount;
                $adv->remaining_amount += $adj->amount;
                $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                $adv->save();
            }
            $adj->delete();
        }

        $paymentVoucher->delete();

        return response()->json([
            'success' => 'Payment voucher deleted successfully!',
        ]);
    }
}
