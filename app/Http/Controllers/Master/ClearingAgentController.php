<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ClearingAgentRequest;
use App\Models\ClearingAgentCompanyBankDetail;
use App\Models\ClearingAgentOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\ClearingAgent;
use App\Models\Master\CompanyLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClearingAgentController extends Controller
{
    public function index()
    {
        return view('management.master.clearing_agent.index');
    }

    public function getList(Request $request)
    {
        $clearingAgents = ClearingAgent::with('company')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%'.$request->search.'%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('owner_name', 'like', $searchTerm)
                        ->orWhere('unique_no', 'like', $searchTerm)
                        ->orWhere('address', 'like', $searchTerm)
                        ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                            $companyQuery->where('name', 'like', $searchTerm);
                        });
                });
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.clearing_agent.getList', compact('clearingAgents'));
    }

    public function create()
    {
        $companyLocation = CompanyLocation::where('status', 'active')->get();
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Clearing Agent');
        })->get();

        return view('management.master.clearing_agent.create', compact('companyLocation', 'accounts'));
    }

    public function store(ClearingAgentRequest $request)
    {
        DB::beginTransaction();

        try {
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('clearing_agents', null, null, 'unique_no');
            $requestData['company_location_ids'] = $request->company_location_ids;

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(
                    getParamsForAccountCreationByPath($request->company_id, $request->name, '2-6', 'clearing_agents')
                );
                $requestData['account_id'] = $account->id;
            }

            $clearingAgent = ClearingAgent::create($requestData);

            $this->storeBankDetails(
                $clearingAgent->id,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'company'
            );

            $this->storeBankDetails(
                $clearingAgent->id,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'owner'
            );

            DB::commit();

            return response()->json([
                'success' => 'Clearing Agent created successfully.',
                'data' => [],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create clearing agent. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function edit($id)
    {
        $clearingAgent = ClearingAgent::with([
            'companyBankDetails',
            'ownerBankDetails',
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::all();
        $selectedLocations = $clearingAgent->company_location_ids ?? [];
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Clearing Agent');
        })->get();

        return view('management.master.clearing_agent.edit', [
            'clearingAgent' => $clearingAgent,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations,
            'accounts' => $accounts,
        ]);
    }

    public function update(ClearingAgentRequest $request, ClearingAgent $clearing_agent)
    {
        DB::beginTransaction();

        try {
            $requestData = $request->all();

            if ($clearing_agent->account) {
                $clearing_agent->account->update([
                    'name' => $request->name,
                ]);
            } elseif ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(
                    getParamsForAccountCreationByPath($request->company_id, $request->name, '2-6', 'clearing_agents')
                );
                $requestData['account_id'] = $account->id;
            }

            $clearing_agent->update($requestData);

            $this->updateBankDetails(
                $clearing_agent,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'companyBankDetails'
            );

            $this->updateBankDetails(
                $clearing_agent,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'ownerBankDetails'
            );

            DB::commit();

            return response()->json([
                'success' => 'Clearing Agent updated successfully.',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update clearing agent. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function storeBankDetails($clearingAgentId, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $type)
    {
        foreach ($bankNames as $index => $bankName) {
            if (empty($bankName)) {
                continue;
            }

            $payload = [
                'bank_name' => $bankName,
                'branch_name' => $branchNames[$index] ?? '',
                'branch_code' => $branchCodes[$index] ?? '',
                'account_title' => $accountTitles[$index] ?? '',
                'account_number' => $accountNumbers[$index] ?? '',
                'clearing_agent_id' => $clearingAgentId,
            ];

            if ($type === 'company') {
                ClearingAgentCompanyBankDetail::create($payload);
            } else {
                ClearingAgentOwnerBankDetail::create($payload);
            }
        }
    }

    protected function updateBankDetails($clearingAgent, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $clearingAgent->{$relation}->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($bankNames as $index => $bankName) {
            if (empty($bankName)) {
                continue;
            }

            $bankData = [
                'bank_name' => $bankName,
                'branch_name' => $branchNames[$index] ?? '',
                'branch_code' => $branchCodes[$index] ?? '',
                'account_title' => $accountTitles[$index] ?? '',
                'account_number' => $accountNumbers[$index] ?? '',
            ];

            if ($index < count($existingIds)) {
                $clearingAgent->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $clearingAgent->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (! empty($toDelete)) {
            $clearingAgent->{$relation}()->whereIn('id', $toDelete)->delete();
        }
    }

    public function destroy(ClearingAgent $clearing_agent)
    {
        $clearing_agent->delete();

        return response()->json(['success' => 'Clearing Agent deleted successfully.'], 200);
    }
}
