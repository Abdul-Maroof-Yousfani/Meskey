<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Acl\Company;
use App\Models\Export\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:currency-list', ['only' => ['index']]);
        $this->middleware('check.company:currency-list', ['only' => ['getTable']]);
        $this->middleware('check.company:currency-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:currency-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:currency-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $currencies = Currency::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.currency.index', compact('currencies'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $companies = Company::get();

        return view('management.export.currency.create', compact('companies'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'currency_name' => 'required|regex:/^[A-Za-z ]+$/|max:255', // Only Letters Allowed
            'currency_code' => 'required|string|max:10',
            'rate' => 'required|numeric|min:0',
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

        $currency = Currency::create([
            'company_id' => $request->input('company'),
            'currency_name' => $request->input('currency_name'),
            'currency_code' => strtoupper($request->input('currency_code')),
            'rate' => $request->input('rate'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Saved.',
            'data' => $currency,
        ], 200);
    }

    public function show(int $id)
    {
        $currency = Currency::findOrFail($id);

        $companies = Company::get();

        return view('management.export.currency.show', compact('currency', 'companies'));
    }

    public function edit(int $id)
    {
        $currency = Currency::findOrFail($id);
        $companies = Company::get();

        return view('management.export.currency.edit', compact('currency', 'companies'));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'currency_name' => 'required|regex:/^[A-Za-z ]+$/|max:255',
            'currency_code' => 'required|string|max:10',
            'rate' => 'required|numeric|min:0',
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

        $currency = Currency::findOrFail($id);

        $currency->update([
            'company_id' => $request->input('company'),
            'currency_name' => $request->input('currency_name'),
            'currency_code' => strtoupper($request->input('currency_code')),
            'rate' => $request->input('rate'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Updated.',
            'data' => $currency,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $currency = Currency::find($id);

            if (! $currency) {
                return response()->json([
                    'error' => 'Currency not found.',
                ], 404);
            }

            $currency->delete();

            DB::commit();

            return response()->json([
                'success' => 'Currency deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCurrencyTable(Request $request)
    {
        $currencies = Currency::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.export.currency.getList', compact('currencies'));
    }
}
