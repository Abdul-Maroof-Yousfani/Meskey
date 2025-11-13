<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\TransactionVoucherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.finance.journal_voucher.index');
    }

    /**
     * Get list of journal vouchers.
     */
    public function getList(Request $request)
    {
        $journalVouchers = JournalVoucher::with(['journalVoucherDetails.account'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('jv_no', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.finance.journal_voucher.getList', compact('journalVouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['accounts'] = Account::where('is_operational', 'yes')
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

        return view('management.finance.journal_voucher.create', $data);
    }

    /**
     * Generate JV number
     */
    public function generateJvNumber(Request $request)
    {
        $request->validate([
            'jv_date' => 'nullable|date'
        ]);

        $prefix = 'JV';
        $jvDate = $request->jv_date ? date('m-d-Y', strtotime($request->jv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $jvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('journal_vouchers', $datePrefix, null, 'jv_no', false);

        return response()->json([
            'success' => true,
            'jv_number' => $uniqueNo
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'jv_date' => 'required|date',
            'jv_no' => 'required|string',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.acc_id' => 'required|exists:accounts,id',
            'details.*.description' => 'nullable|string',
            'details.*.debit_amount' => 'nullable|numeric|min:0',
            'details.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $index => $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

            $debitAmount = round($debitAmount, 2);
            $creditAmount = round($creditAmount, 2);

            if ($debitAmount <= 0 && $creditAmount <= 0) {
                return response()->json([
                    'error' => 'Each line item must have either a debit or credit amount greater than zero.'
                ], 422);
            }

            if ($debitAmount > 0 && $creditAmount > 0) {
                return response()->json([
                    'error' => 'Each line item can only have either a debit or a credit amount, not both.'
                ], 422);
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        if (abs($totalDebits - $totalCredits) > 0.01) {
            return response()->json([
                'error' => 'Total debits must equal total credits. Debits: ' . number_format($totalDebits, 2) . ', Credits: ' . number_format($totalCredits, 2)
            ], 422);
        }

        DB::transaction(function () use ($request) {
            $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

            $journalVoucher = JournalVoucher::create([
                'jv_date' => $request->jv_date,
                'jv_no' => $request->jv_no,
                'description' => $request->description,
                'username' => $username,
                'status' => 'active',
                'jv_status' => 'pending',
                'company_id' => Auth::user()->current_company_id ?? null
            ]);

            foreach ($request->details as $detail) {
                $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
                $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

                $debitAmount = round($debitAmount, 2);
                $creditAmount = round($creditAmount, 2);

                JournalVoucherDetail::create([
                    'journal_voucher_id' => $journalVoucher->id,
                    'acc_id' => $detail['acc_id'],
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'description' => $detail['description'] ?? null,
                    'username' => $username,
                    'status' => 'active',
                    'timestamp' => now()
                ]);
            }
        });

        return response()->json([
            'success' => 'Journal voucher created successfully!',
            'redirect' => route('journal-voucher.index')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $journalVoucher = JournalVoucher::with([
            'journalVoucherDetails.account',
            'approveUser',
            'deleteUser'
        ])->findOrFail($id);

        return view('management.finance.journal_voucher.show', compact('journalVoucher'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $journalVoucher = JournalVoucher::with(['journalVoucherDetails.account'])->findOrFail($id);

        // Prevent editing approved or rejected vouchers
        if ($journalVoucher->jv_status !== 'pending') {
            return redirect()->route('journal-voucher.index')
                ->with('error', 'Cannot edit a journal voucher that has been approved or rejected.');
        }

        $data = [
            'journalVoucher' => $journalVoucher,
            'accounts' => Account::where('is_operational', 'yes')
                ->whereHas('parent', function ($q) {
                    $q->where('hierarchy_path', '2')
                        ->orWhereHas('parent', function ($q2) {
                            $q2->where('hierarchy_path', '2')
                                ->orWhereHas('parent', function ($q3) {
                                    $q3->where('hierarchy_path', '2');
                                });
                        });
                })
                ->get()
        ];

        return view('management.finance.journal_voucher.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        // Prevent updating approved or rejected vouchers
        if ($journalVoucher->jv_status !== 'pending') {
            return response()->json([
                'error' => 'Cannot update a journal voucher that has been approved or rejected.'
            ], 422);
        }

        $request->validate([
            'jv_date' => 'required|date',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.acc_id' => 'required|exists:accounts,id',
            'details.*.description' => 'nullable|string',
            'details.*.debit_amount' => 'nullable|numeric|min:0',
            'details.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

            if ($debitAmount <= 0 && $creditAmount <= 0) {
                return response()->json([
                    'error' => 'Each line item must have either a debit or credit amount greater than zero.'
                ], 422);
            }

            if ($debitAmount > 0 && $creditAmount > 0) {
                return response()->json([
                    'error' => 'Each line item can only have either a debit or a credit amount, not both.'
                ], 422);
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        if (abs($totalDebits - $totalCredits) > 0.01) {
            return response()->json([
                'error' => 'Total debits must equal total credits. Debits: ' . number_format($totalDebits, 2) . ', Credits: ' . number_format($totalCredits, 2)
            ], 422);
        }

        DB::transaction(function () use ($request, $journalVoucher) {
            $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

            $journalVoucher->update([
                'jv_date' => $request->jv_date,
                'description' => $request->description,
                'username' => $username,
                'company_id' => Auth::user()->current_company_id ?? $journalVoucher->company_id
            ]);

            // Delete old details
            JournalVoucherDetail::where('journal_voucher_id', $journalVoucher->id)->delete();

            // Create new details
            foreach ($request->details as $detail) {
                $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
                $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

                $debitAmount = round($debitAmount, 2);
                $creditAmount = round($creditAmount, 2);

                JournalVoucherDetail::create([
                    'journal_voucher_id' => $journalVoucher->id,
                    'acc_id' => $detail['acc_id'],
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'description' => $detail['description'] ?? null,
                    'username' => $username,
                    'status' => 'active',
                    'timestamp' => now(),
                    'company_id' => $request->company_i
                ]);
            }
        });

        return response()->json([
            'success' => 'Journal voucher updated successfully!',
            'redirect' => route('journal-voucher.index')
        ]);
    }

    /**
     * Approve journal voucher and create transactions
     */
    public function approve(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::with(['journalVoucherDetails.account'])->findOrFail($id);

        // Check if already approved
        if ($journalVoucher->jv_status === 'approved') {
            return response()->json([
                'error' => 'Journal voucher is already approved.'
            ], 422);
        }

        // Check if already rejected
        if ($journalVoucher->jv_status === 'rejected') {
            return response()->json([
                'error' => 'Cannot approve a rejected journal voucher.'
            ], 422);
        }

        DB::transaction(function () use ($journalVoucher) {
            // Get Journal Voucher transaction voucher type ID
            $voucherType = TransactionVoucherType::where('code', 'JV')->first();
            if (!$voucherType) {
                throw new \Exception('Journal Voucher transaction type not found');
            }

            // Create transactions for each journal entry
            foreach ($journalVoucher->journalVoucherDetails as $detail) {
                if ($detail->debit_amount > 0) {
                    createTransaction(
                        $detail->debit_amount,
                        $detail->acc_id,
                        $voucherType->id,
                        $journalVoucher->jv_no,
                        'debit',
                        'no',
                        [
                            'purpose' => "journal-voucher-{$journalVoucher->id}-{$journalVoucher->jv_no}",
                            'remarks' => $detail->description ?? ($journalVoucher->description ?? "Journal entry for {$journalVoucher->jv_no}"),
                            'voucher_date' => $journalVoucher->jv_date->format('Y-m-d')
                        ]
                    );
                }

                if ($detail->credit_amount > 0) {
                    createTransaction(
                        $detail->credit_amount,
                        $detail->acc_id,
                        $voucherType->id,
                        $journalVoucher->jv_no,
                        'credit',
                        'no',
                        [
                            'purpose' => "journal-voucher-{$journalVoucher->id}-{$journalVoucher->jv_no}",
                            'remarks' => $detail->description ?? ($journalVoucher->description ?? "Journal entry for {$journalVoucher->jv_no}"),
                            'voucher_date' => $journalVoucher->jv_date->format('Y-m-d')
                        ]
                    );
                }
            }

            // Update journal voucher status
            $journalVoucher->update([
                'jv_status' => 'approved',
                'approve_user_id' => optional(Auth::user())->id
            ]);
        });

        return response()->json([
            'success' => 'Journal voucher approved successfully and transactions created!'
        ]);
    }

    /**
     * Reject journal voucher
     */
    public function reject(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        // Check if already approved
        if ($journalVoucher->jv_status === 'approved') {
            return response()->json([
                'error' => 'Cannot reject an approved journal voucher. Please reverse the transactions first.'
            ], 422);
        }

        $journalVoucher->update([
            'jv_status' => 'rejected',
            'approve_user_id' => optional(Auth::user())->id
        ]);

        return response()->json([
            'success' => 'Journal voucher rejected successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        // Prevent deleting approved or rejected vouchers
        if ($journalVoucher->jv_status !== 'pending') {
            return response()->json([
                'error' => 'Cannot delete a journal voucher that has been approved or rejected.'
            ], 422);
        }

        $journalVoucher->update([
            'delete_user_id' => optional(Auth::user())->id
        ]);

        $journalVoucher->delete();

        return response()->json([
            'success' => 'Journal voucher deleted successfully!'
        ]);
    }
}