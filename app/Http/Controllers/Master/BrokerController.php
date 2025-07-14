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
        return view('management.master.broker.create', compact('companyLocation'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrokerRequest $request)
    {
        // $data = $request->validated();
        // $request = $request->all();

        // $request['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
        // $broker = Broker::create($request);

        // return response()->json(['success' => 'Broker created successfully.', 'data' => $broker], 201);
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;

            $requestData['company_location_ids'] = $request->company_location_ids;

            $account = Account::create(getParamsForAccountCreation($request->company_id, $request->company_name, 'Broker'));
            $requestData['account_id'] = $account->id;

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

            // Save owner bank details
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

            // if ($request->has('create_as_broker') && $request->create_as_broker) {
            //     $account = Account::create(getParamsForAccountCreation($request->company_id, $request->company_name, 'Broker'));

            //     $brokerData = [
            //         'company_id' => $broker->company_id ?? null,
            //         'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
            //         'name' => $broker->company_name,
            //         'account_id' => $account->id,
            //         'email' => $broker->email ?? null,
            //         'phone' => $broker->phone ?? null,
            //         'address' => $broker->address ?? null,
            //         'ntn' => $broker->ntn ?? null,
            //         'stn' => $broker->stn ?? null,
            //         'status' => $broker->status,
            //     ];

            // $broker = Broker::create($brokerData);
            // }

            DB::commit();

            return response()->json([
                'success' => 'Broker created successfully.',
                'data' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error('Supplier creation failed: ' . $e->getMessage());

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
        // $broker = Broker::findOrFail($id);
        // return view('management.master.broker.edit', compact('broker'));

        $broker = Broker::with([
            'companyBankDetails',
            'ownerBankDetails'
        ])->findOrFail($id);
        // dd($broker->company_location_ids);
        $companyLocations = CompanyLocation::all(); // Assuming you have a CompanyLocation model

        // Decode the JSON locations if needed
        $selectedLocations = $broker->company_location_ids ?? [];

        return view('management.master.broker.edit', [
            'broker' => $broker,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations
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

            if (empty($broker->account_id)) {
                $account = Account::create(getParamsForAccountCreation(
                    $request->company_id,
                    $request->company_name,
                    'Broker'
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

            // if ($request->has('create_as_broker')) {
            //     $brokerData = [
            //         'company_id' => $broker->company_id ?? null,
            //         'name' => $broker->company_name,
            //         'email' => $broker->email ?? null,
            //         'phone' => $broker->phone ?? null,
            //         'address' => $broker->address ?? null,
            //         'ntn' => $broker->ntn ?? null,
            //         'stn' => $broker->stn ?? null,
            //         'status' => $broker->status,
            //     ];

            //     if ($broker->broker) {
            //         if (empty($broker->broker->account_id)) {
            //             $account = Account::create(getParamsForAccountCreation(
            //                 $request->company_id,
            //                 $request->company_name,
            //                 'Broker'
            //             ));
            //             $brokerData['account_id'] = $account->id;
            //         }
            //         $broker->broker->update($brokerData);
            //     } else {
            //         $brokerData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
            //         $account = Account::create(getParamsForAccountCreation(
            //             $request->company_id,
            //             $request->company_name,
            //             'Broker'
            //         ));
            //         $brokerData['account_id'] = $account->id;
            //         $broker->broker()->create($brokerData);
            //     }
            // } elseif ($broker->broker) {
            //     $broker->broker->delete();
            // }

            DB::commit();

            return response()->json([
                'success' => 'Supplier updated successfully.',
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error('Supplier update failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update supplier. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
        // $data = $request->validated();
        // $broker = Broker::findOrFail($id);
        // $broker->update($data);

        // return response()->json(['success' => 'Broker updated successfully.', 'data' => $broker], 200);
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
