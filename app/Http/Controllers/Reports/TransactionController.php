<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\cr;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use Illuminate\Http\Request;
use DB;

class TransactionController extends Controller
{
    public function index()
    {
        $accounts = Account::getTree();

        return view('management.reports.transaction.index', compact('accounts'));
    }

    /**
     * Get list of categories.
     */
    public function getTransactionsReport(Request $request)
    {
        $openingBalance = 0;
        if ($request->filled('account_id') && $request->filled('start_date')) {
            $openingBalance = Transaction::where('account_id', $request->account_id)
                ->where('voucher_date', '<', $request->start_date)
                ->sum(DB::raw("CASE WHEN type = 'debit' THEN amount ELSE -amount END"));
        }

        $query = Transaction::with(['account'])
            ->orderBy('voucher_date', 'asc')
            ->orderBy('created_at', 'asc');

        $accountName = null;
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);

            $account = Account::find($request->account_id);
            $accountName = $account ? $account->name : null;
        }

        $query->when($request->filled('daterange'), function ($q) use ($request) {
            $dates = explode(' - ', $request->daterange);
            $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
            $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

            return $q->whereDate('voucher_date', '>=', $startDate)
                ->whereDate('voucher_date', '<=', $endDate);
        });

        $transactions = $query->get();

        $daterange = $request->daterange;

        return view('management.reports.transaction.getList', compact('transactions', 'openingBalance', 'daterange', 'accountName'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(cr $cr)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(cr $cr)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cr $cr)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cr $cr)
    {
        //
    }
}
