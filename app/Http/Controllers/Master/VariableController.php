<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\Variable;
use App\Models\Master\Account\Account;
use Illuminate\Support\Facades\DB;

class VariableController extends Controller
{
    public function index() {
        return view('management.master.variable.index');
    }

    public function getList(Request $request) {
        $variables = Variable::with('account')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm]);
            })
            ->latest('id')
            ->paginate(25);

        return view('management.master.variable.getList', compact('variables'));
    }

    public function create() {
        return view('management.master.variable.create');
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'company_id' => 'nullable|integer'
        ]);

        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->check() ? auth()->user()->current_company_id : 1;
        }
        $data['status'] = $data['status'] ?? 1;

        DB::beginTransaction();
        try {
            // Auto open COA account under Accrued Operational Liability (2-10)
            $accountParams = getParamsForAccountCreationByPath($data['company_id'], $data['title'], '2-10', 'variables');
            $account = Account::create($accountParams);

            $data['account_id'] = $account->id;
            $variable = Variable::create($data);

            $account->update(['model_id' => $variable->id]);

            DB::commit();
            return response()->json(['success' => 'Variable created successfully with COA Account.', 'data' => $variable], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create variable: ' . $e->getMessage()], 500);
        }
    }

    public function edit(int $id) {
        $variable = Variable::with('account')->findOrFail($id);
        return view('management.master.variable.edit', compact('variable'));
    }

    public function update(Request $request, int $id) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'company_id' => 'nullable|integer'
        ]);
        
        $variable = Variable::findOrFail($id);
        $companyId = $data['company_id'] ?? $variable->company_id ?? (auth()->check() ? auth()->user()->current_company_id : 1);

        DB::beginTransaction();
        try {
            $account = $variable->account_id ? Account::find($variable->account_id) : null;

            // Agar account nahi hai tou hee open ho, warna srf update ho
            if (!$account) {
                $accountParams = getParamsForAccountCreationByPath($companyId, $data['title'], '2-10', 'variables');
                $account = Account::create($accountParams);
                $data['account_id'] = $account->id;
                $account->update(['model_id' => $variable->id]);
            } else {
                $account->update([
                    'name' => $data['title']
                ]);
            }

            $variable->update($data);

            DB::commit();
            return response()->json(['success' => 'Variable updated successfully.', 'data' => $variable], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update variable: ' . $e->getMessage()], 500);
        }
    }
    
    public function show(int $id) {
        $variable = Variable::with('account')->findOrFail($id);
        return view('management.master.variable.show', compact('variable'));
    }
}
