<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Broker;
use Illuminate\Http\Request;
use App\Http\Requests\Master\BrokerRequest;
use App\Models\BrokerCompanyBankDetail;
use App\Models\BrokerOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\CompanyLocation;
use Illuminate\Support\Facades\DB;

class BrokerController extends Controller
{


    function __construct()
    {
        $this->middleware('check.company:raw-material-broker', ['only' => ['index', 'edit', 'getList']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.broker.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        // $brokers = Broker::when($request->filled('search'), function ($q) use ($request) {
        //     $searchTerm = '%' . $request->search . '%';
        //     return $q->where(function ($sq) use ($searchTerm) {
        //         $sq->where('name', 'like', $searchTerm);
        //     });
        // })
        //     ->latest()
        //     ->paginate(request('per_page', 25));

        // return view('management.master.broker.getList', compact('brokers'));

        $brokers = Broker::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        //dd($brokers->first()->company_location_ids);
        return view('management.master.broker.getList', compact('brokers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocation = CompanyLocation::where('status', 'active')->get();
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Supplier')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.broker.create', compact('companyLocation', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrokerRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;
            $requestData['company_location_ids'] = $request->company_location_ids;

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(getParamsForAccountCreation($request->company_id, $request->company_name, 'brokers'));
                $requestData['account_id'] = $account->id;
            }

            $broker = Broker::create($requestData);

            if (!empty($request->company_bank_name)) {
                foreach ($request->company_bank_name as $key => $bankName) {
                    if (empty($bankName)) continue;

                    BrokerCompanyBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->company_branch_name[$key] ?? '',
                        'branch_code' => $request->company_branch_code[$key] ?? '',
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'broker_id' => $broker->id
                    ]);
                }
            }

            if (!empty($request->owner_bank_name)) {
                foreach ($request->owner_bank_name as $key => $bankName) {
                    if (empty($bankName)) continue;

                    BrokerOwnerBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->owner_branch_name[$key] ?? '',
                        'branch_code' => $request->owner_branch_code[$key] ?? '',
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'broker_id' => $broker->id
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Broker created successfully.',
                'data' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create broker. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $broker = Broker::with([
            'companyBankDetails',
            'ownerBankDetails'
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::all();
        $selectedLocations = $broker->company_location_ids ?? [];
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Supplier')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.broker.edit', [
            'broker' => $broker,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrokerRequest $request, Broker $broker)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } elseif (empty($broker->account_id)) {
                $account = Account::create(getParamsForAccountCreation(
                    $request->company_id,
                    $request->company_name,
                    'brokers'
                ));
                $requestData['account_id'] = $account->id;
            }

            $broker->update($requestData);

            $this->updateBankDetails(
                $broker,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'companyBankDetails'
            );

            $this->updateBankDetails(
                $broker,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'ownerBankDetails'
            );

            DB::commit();

            return response()->json([
                'success' => 'Broker updated successfully.',
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update broker. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    protected function updateBankDetails($broker, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $broker->{$relation}->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($bankNames as $index => $bankName) {
            if (empty($bankName)) continue;

            $bankData = [
                'bank_name' => $bankName,
                'branch_name' => $branchNames[$index] ?? '',
                'branch_code' => $branchCodes[$index] ?? '',
                'account_title' => $accountTitles[$index] ?? '',
                'account_number' => $accountNumbers[$index] ?? '',
            ];

            if ($index < count($existingIds)) {
                $broker->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $broker->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            $broker->{$relation}()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = Broker::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Broker deleted successfully.'], 200);
    }
}
