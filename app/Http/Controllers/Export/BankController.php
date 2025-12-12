<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Acl\Company;
use App\Models\Export\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:bank-list', ['only' => ['index']]);
        $this->middleware('check.company:bank-list', ['only' => ['getTable']]);
        $this->middleware('check.company:bank-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:bank-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:bank-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $banks = Bank::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.bank.index', compact('banks'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $companies = Company::get();

        return view('management.export.bank.create', compact('companies'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'account_title' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'iban' => 'required|string|max:34', // IBAN max length
            'account_no' => 'required|string|max:20', // Account No max length
            'swift_code' => 'nullable|string|max:20',
            'bank_address' => 'nullable|string|max:255', // Added bank_address
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $bank = Bank::create([
            'company_id' => $request->input('company'),
            'account_title' => $request->input('account_title'),
            'bank_name' => $request->input('bank_name'),
            'iban' => strtoupper($request->input('iban')),
            'account_no' => $request->input('account_no'),
            'swift_code' => $request->input('swift_code'),
            'bank_address' => $request->input('bank_address'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Bank account successfully saved.',
            'data' => $bank,
        ], 200);
    }

    public function show(int $id)
    {
        $bank = Bank::findOrFail($id);

        $companies = Company::get();

        return view('management.export.bank.show', compact('bank', 'companies'));
    }

    public function edit(int $id)
    {
        $bank = Bank::findOrFail($id);
        $companies = Company::get();

        return view('management.export.bank.edit', compact('bank', 'companies'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bank = Bank::findOrFail($id);

        $rules = [
            'company' => 'required|exists:companies,id',
            'account_title' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'iban' => 'required|string|max:34',
            'account_no' => 'required|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'bank_address' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $bank->update([
            'company_id' => $request->input('company'),
            'account_title' => $request->input('account_title'),
            'bank_name' => $request->input('bank_name'),
            'iban' => strtoupper($request->input('iban')),
            'account_no' => $request->input('account_no'),
            'swift_code' => $request->input('swift_code'),
            'bank_address' => $request->input('bank_address'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Bank account successfully updated.',
            'data' => $bank,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $bank = Bank::find($id);

            if (! $bank) {
                return response()->json([
                    'error' => 'Bank account not found.',
                ], 404);
            }

            $bank->delete();

            DB::commit();

            return response()->json([
                'success' => 'Bank account deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getBankTable(Request $request)
    {
        $banks = Bank::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.export.bank.getList', compact('banks'));
    }
}
