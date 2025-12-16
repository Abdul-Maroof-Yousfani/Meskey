<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Acl\Company;
use App\Models\Master\HsCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HsCodeController extends Controller
{
    public function index(Request $request): View
    {
        $codes = HsCode::orderBy('id', 'ASC')->paginate(0);

        return view('management.master.hscode.index', compact('codes'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $companies = Company::get();

        return view('management.master.hscode.create', compact('companies'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'code' => 'required|string|max:20|unique:hs_codes,code',
            'description' => 'nullable|string|max:1000',

            'custom_duty' => 'required|numeric|min:0',       // amount
            'excise_duty' => 'required|numeric|min:0|max:100', // percentage
            'sales_tax' => 'required|numeric|min:0|max:100', // percentage
            'income_tax' => 'required|numeric|min:0|max:100', // percentage

            'status' => 'required|in:active,inactive',
        ];

        $messages = [
            'company.required' => 'Please select a company.',
            'company.exists' => 'The selected company is invalid.',

            'code.required' => 'HS Code is required.',
            'code.max' => 'HS Code must not exceed 20 characters.',
            'code.unique' => 'This HS Code already exists.',

            'description.max' => 'Description cannot exceed 1000 characters.',

            'custom_duty.required' => 'Custom duty amount is required.',
            'custom_duty.numeric' => 'Custom duty must be a valid number.',
            'custom_duty.min' => 'Custom duty cannot be negative.',

            'excise_duty.required' => 'Excise duty percentage is required.',
            'excise_duty.numeric' => 'Excise duty must be a number.',
            'excise_duty.min' => 'Excise duty cannot be less than 0%.',
            'excise_duty.max' => 'Excise duty cannot exceed 100%.',

            'sales_tax.required' => 'Sales tax percentage is required.',
            'sales_tax.numeric' => 'Sales tax must be a number.',
            'sales_tax.min' => 'Sales tax cannot be less than 0%.',
            'sales_tax.max' => 'Sales tax cannot exceed 100%.',

            'income_tax.required' => 'Income tax percentage is required.',
            'income_tax.numeric' => 'Income tax must be a number.',
            'income_tax.min' => 'Income tax cannot be less than 0%.',
            'income_tax.max' => 'Income tax cannot exceed 100%.',

            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $hs = HsCode::create([
            'company_id' => $request->input('company'),
            'code' => strtoupper($request->input('code')),
            'description' => $request->input('description'),

            'custom_duty' => $request->input('custom_duty'),
            'excise_duty' => $request->input('excise_duty'),
            'sales_tax' => $request->input('sales_tax'),
            'income_tax' => $request->input('income_tax'),

            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'HS Code successfully saved.',
            'data' => $hs,
        ], 200);
    }

    public function show(int $id)
    {
        $hs = HsCode::findOrFail($id);

        $companies = Company::get();

        return view('management.master.hscode.show', compact('hs', 'companies'));
    }

    public function edit(int $id)
    {
        $hs = HsCode::findOrFail($id);
        $companies = Company::get();

        return view('management.master.hscode.edit', compact('hs', 'companies'));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'code' => 'required|string|max:20|unique:hs_codes,code,'.$id,
            'description' => 'nullable|string|max:1000',

            'custom_duty' => 'required|numeric|min:0',
            'excise_duty' => 'required|numeric|min:0|max:100',
            'sales_tax' => 'required|numeric|min:0|max:100',
            'income_tax' => 'required|numeric|min:0|max:100',

            'status' => 'required|in:active,inactive',
        ];

        $messages = [
            'company.required' => 'Please select a company.',
            'company.exists' => 'The selected company is invalid.',

            'code.required' => 'HS Code is required.',
            'code.max' => 'HS Code must not exceed 20 characters.',
            'code.unique' => 'This HS Code is already assigned.',

            'description.max' => 'Description cannot exceed 1000 characters.',

            'custom_duty.required' => 'Custom duty amount is required.',
            'custom_duty.numeric' => 'Custom duty must be a valid number.',
            'custom_duty.min' => 'Custom duty cannot be negative.',

            'excise_duty.required' => 'Excise duty percentage is required.',
            'excise_duty.numeric' => 'Excise duty must be a number.',
            'excise_duty.min' => 'Excise duty cannot be less than 0%.',
            'excise_duty.max' => 'Excise duty cannot exceed 100%.',

            'sales_tax.required' => 'Sales tax percentage is required.',
            'sales_tax.numeric' => 'Sales tax must be a number.',
            'sales_tax.min' => 'Sales tax cannot be less than 0%.',
            'sales_tax.max' => 'Sales tax cannot exceed 100%.',

            'income_tax.required' => 'Income tax percentage is required.',
            'income_tax.numeric' => 'Income tax must be a number.',
            'income_tax.min' => 'Income tax cannot be less than 0%.',
            'income_tax.max' => 'Income tax cannot exceed 100%.',

            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $hs = HsCode::findOrFail($id);

        $hs->update([
            'company_id' => $request->input('company'),
            'code' => strtoupper($request->input('code')),
            'description' => $request->input('description'),

            'custom_duty' => $request->input('custom_duty'),
            'excise_duty' => $request->input('excise_duty'),
            'sales_tax' => $request->input('sales_tax'),
            'income_tax' => $request->input('income_tax'),

            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'HS Code successfully updated.',
            'data' => $hs,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $hs = HsCode::find($id);

            if (! $hs) {
                return response()->json([
                    'error' => 'HS Code not found.',
                ], 404);
            }

            $hs->delete();

            DB::commit();

            return response()->json([
                'success' => 'HS Code deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getHsCodeTable(Request $request)
    {
        $codes = HsCode::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.master.hscode.getList', compact('codes'));
    }
}
