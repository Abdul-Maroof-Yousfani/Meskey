<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
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
            'details.*.debit_credit' => 'required|in:debit,credit',
            'details.*.amount' => 'required|numeric|min:0.01',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $detail) {
            if ($detail['debit_credit'] === 'debit') {
                $totalDebits += $detail['amount'];
            } else {
                $totalCredits += $detail['amount'];
            }
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
                'jv_status' => 'pending'
            ]);

            foreach ($request->details as $detail) {
                JournalVoucherDetail::create([
                    'journal_voucher_id' => $journalVoucher->id,
                    'acc_id' => $detail['acc_id'],
                    'debit_credit' => $detail['debit_credit'],
                    'amount' => $detail['amount'],
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
            'journalVoucherDetails.account'
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
            'details.*.debit_credit' => 'required|in:debit,credit',
            'details.*.amount' => 'required|numeric|min:0.01',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $detail) {
            if ($detail['debit_credit'] === 'debit') {
                $totalDebits += $detail['amount'];
            } else {
                $totalCredits += $detail['amount'];
            }
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
                'username' => $username
            ]);

            // Delete old details
            JournalVoucherDetail::where('journal_voucher_id', $journalVoucher->id)->delete();

            // Create new details
            foreach ($request->details as $detail) {
                JournalVoucherDetail::create([
                    'journal_voucher_id' => $journalVoucher->id,
                    'acc_id' => $detail['acc_id'],
                    'debit_credit' => $detail['debit_credit'],
                    'amount' => $detail['amount'],
                    'username' => $username,
                    'status' => 'active',
                    'timestamp' => now()
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

        DB::transaction(function () use ($journalVoucher, $request) {
            $username = Auth::user()->name ?? Auth::user()->email ?? 'System';
            
            // Get Journal Voucher transaction voucher type ID
            $voucherType = TransactionVoucherType::where('code', 'JV')->first();
            if (!$voucherType) {
                throw new \Exception('Journal Voucher transaction type not found');
            }

            // Create transactions for each journal entry
            foreach ($journalVoucher->journalVoucherDetails as $detail) {
                createTransaction(
                    $detail->amount,
                    $detail->acc_id,
                    $voucherType->id,
                    $journalVoucher->jv_no,
                    $detail->debit_credit,
                    'no',
                    [
                        'purpose' => "journal-voucher-{$journalVoucher->id}-{$journalVoucher->jv_no}",
                        'remarks' => $journalVoucher->description ?? "Journal entry for {$journalVoucher->jv_no}",
                        'voucher_date' => $journalVoucher->jv_date->format('Y-m-d')
                    ]
                );
            }

            // Update journal voucher status
            $journalVoucher->update([
                'jv_status' => 'approved',
                'approve_username' => $username
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

        $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

        $journalVoucher->update([
            'jv_status' => 'rejected',
            'approve_username' => $username
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

        $username = Auth::user()->name ?? Auth::user()->email ?? 'System';
        
        $journalVoucher->update([
            'delete_username' => $username
        ]);
        
        $journalVoucher->delete();

        return response()->json([
            'success' => 'Journal voucher deleted successfully!'
        ]);
    }
}