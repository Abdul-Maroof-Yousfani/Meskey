<?php


namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Station;
use App\Http\Requests\Master\AccountRequest;
use App\Models\Master\Account\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.account.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $accounts = Account::with('children')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm);
                });
            })
            ->when(!$request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereNull('parent_id');
                });
            })
           // ->whereNull('parent_id')
            ->paginate(request('per_page', 10000));

        return view('management.master.account.getList', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.account.create', ['parentAccounts' => Account::where('is_operational', 'no')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['company_id'] = $request->company_id;
        $validatedData['request_account_id'] = 0;

        $account = Account::create($validatedData);

        return response()->json([
            'success' => 'Account created successfully.',
            'data' => $account
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $account = Account::findOrFail($id);
        return view('management.master.account.edit', ['account' => $account, 'parentAccounts' => Account::where('is_operational', 'no')->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, $id)
    {
        $data = $request->validated();
        $broker = Account::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Account updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = Account::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Account deleted successfully.'], 200);
    }
}
