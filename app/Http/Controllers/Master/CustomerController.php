<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\CustomerRequest;
use App\Models\CustomerCompanyBankDetail;
use App\Models\CustomerOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.customer.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $Customers = Customer::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        // dd($Suppliers->first()->company_location_ids);
        return view('management.master.customer.getList', compact('Customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocation = CompanyLocation::where('status', 'active')->get();
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'customer')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.customer.create', compact('companyLocation', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storebk(CustomerRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();

        $request['unique_no'] = generateUniqueNumber('customers', null, null, 'unique_no');
        $Customer = Customer::create($request);

        return response()->json(['success' => 'Customer created successfully.', 'data' => $Customer], 201);
    }

    public function store(CustomerRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('customers', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;
            $requestData['company_location_ids'] = $request->company_location_ids;

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '1-5', 'customers'));
                $requestData['account_id'] = $account->id;
            }

            $customer = Customer::create($requestData);

            if (! empty($request->company_bank_name)) {
                foreach ($request->company_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    CustomerCompanyBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->company_branch_name[$key] ?? '',
                        'branch_code' => $request->company_branch_code[$key] ?? '',
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'customer_id' => $customer->id,
                    ]);
                }
            }

            if (! empty($request->owner_bank_name)) {
                foreach ($request->owner_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    CustomerOwnerBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->owner_branch_name[$key] ?? '',
                        'branch_code' => $request->owner_branch_code[$key] ?? '',
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'customer_id' => $customer->id,
                    ]);
                }
            }

            if ($request->has('create_as_broker') && $request->create_as_broker) {

                $Brokeraccount = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-3', 'brokers'));

                $brokerData = [
                    'company_id' => $customer->company_id ?? null,
                    'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                    'name' => $customer->company_name,
                    'account_id' => $Brokeraccount->id,
                    'email' => $customer->email ?? null,
                    'phone' => $customer->phone ?? null,
                    'address' => $customer->address ?? null,
                    'ntn' => $customer->ntn ?? null,
                    'stn' => $customer->stn ?? null,
                    'status' => $customer->status,
                ];

                $broker = Broker::create($brokerData);
            }

            DB::commit();

            return response()->json([
                'success' => 'Customer created successfully.',
                'data' => [],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create customer. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::with([
            'companyBankDetails',
            'ownerBankDetails',
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::all();
        $selectedLocations = $customer->company_location_ids ?? [];
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Customer')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.customer.edit', [
            'customer' => $customer,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequest $request, Customer $customer)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            if ($customer->account) {
                // Existing account update
                $customer->account->update([
                    'name' => $request->company_name,
                ]);
            } elseif ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                // New account create
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '1-5', 'customers'));
                $requestData['account_id'] = $account->id;
            }

            $customer->update($requestData);

            $this->updateBankDetails(
                $customer,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'companyBankDetails'
            );

            $this->updateBankDetails(
                $customer,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'ownerBankDetails'
            );

            if ($request->has('create_as_broker')) {
                $brokerData = [
                    'company_id' => $customer->company_id ?? null,
                    'name' => $customer->company_name,
                    'email' => $customer->email ?? null,
                    'phone' => $customer->phone ?? null,
                    'address' => $customer->address ?? null,
                    'ntn' => $customer->ntn ?? null,
                    'stn' => $customer->stn ?? null,
                    'status' => $customer->status,
                ];

                if ($customer->broker) {
                    if ($request->account_id) {
                        $brokerData['account_id'] = $request->account_id;
                    } elseif (empty($customer->broker->account_id)) {
                        $brokerData['account_id'] = $customer->account_id;
                    }
                    $customer->broker->update($brokerData);
                } else {
                    $brokerData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
                    $brokerData['account_id'] = $request->account_id ?: $customer->account_id;
                    $customer->broker()->create($brokerData);
                }
            } elseif ($customer->broker) {
                $customer->broker->delete();
            }

            DB::commit();

            return response()->json([
                'success' => 'Customer updated successfully.',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update Customer. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function updateBankDetails($customer, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $customer->{$relation}->pluck('id')->toArray();
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
                $customer->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $customer->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (! empty($toDelete)) {
            $customer->{$relation}()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['success' => 'Customer deleted successfully.'], 200);
    }
}
