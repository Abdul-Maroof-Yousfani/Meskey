<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\CustomerRequest;
use App\Models\CustomerCompanyBankDetail;
use App\Models\CustomerConsignee;
use App\Models\CustomerOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Customer;
use App\Models\Export\ExportOrder;
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
        $Customers = Customer::with(['account.parent'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%'.$request->search.'%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('company_name', 'like', $searchTerm)
                        ->orWhere('owner_name', 'like', $searchTerm)
                        ->orWhere('unique_no', 'like', $searchTerm)
                        ->orWhereHas('account', function ($aq) use ($searchTerm) {
                            $aq->where('hierarchy_path', 'like', $searchTerm)
                                ->orWhere('unique_no', 'like', $searchTerm)
                                ->orWhere('name', 'like', $searchTerm);
                        });
                });
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

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

            $companyId = $request->company_id ?: (auth()->user()?->current_company_id ?? auth()->user()?->company_id ?? 1);
            $companyName = trim($request->company_name);
            $ownerName = trim($request->owner_name);

            // Double-check duplicate customer: same company_name AND same owner_name
            $duplicateCustomer = Customer::where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
                ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)])
                ->exists();

            if ($duplicateCustomer) {
                return response()->json([
                    'error' => "A customer with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists.",
                    'errors' => ['company_name' => ["A customer with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists."]],
                ], 422);
            }

            $requestData['company_id'] = $companyId;
            $requestData['unique_no'] = generateUniqueNumber('customers', null, null, 'unique_no');
            $requestData['name'] = $companyName;
            $requestData['company_name'] = $companyName;
            $requestData['owner_name'] = $ownerName;
            $requestData['company_location_ids'] = $request->company_location_ids;

            // COA Account Deduplication / Reuse
            if (!empty($request->account_id)) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $existingAccount = Account::where('company_id', $companyId)
                    ->where(function ($q) {
                        $q->where('table_name', 'customers')
                          ->orWhere('hierarchy_path', 'like', '1-5-%');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                    ->first();

                if ($existingAccount && !Customer::where('account_id', $existingAccount->id)->exists()) {
                    $requestData['account_id'] = $existingAccount->id;
                } else {
                    $accountName = $existingAccount ? "{$companyName} - {$ownerName}" : $companyName;
                    $account = Account::create(getParamsForAccountCreationByPath($companyId, $accountName, '1-5', 'customers'));
                    $requestData['account_id'] = $account->id;
                }
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

            // Save consignees
            if ($request->has('has_consignee') && $request->has_consignee) {
                $consigneeNames = $request->consignee_name ?? [];
                foreach ($consigneeNames as $key => $consigneeName) {
                    if (empty($consigneeName)) {
                        continue;
                    }
                    CustomerConsignee::create([
                        'customer_id'    => $customer->id,
                        'name'           => $consigneeName,
                        'address'        => $request->consignee_address[$key] ?? '',
                        'contact'        => $request->consignee_contact[$key] ?? '',
                        'contact_person' => $request->consignee_contact_person[$key] ?? '',
                        'email'          => $request->consignee_email[$key] ?? '',
                    ]);
                }
            }

            if ($request->has('create_as_broker') && $request->create_as_broker) {
                $existingBroker = Broker::where('company_id', $companyId)
                    ->where(function ($q) use ($companyName, $ownerName) {
                        $q->where(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                        })
                        ->orWhere(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                        })
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)]);
                    })
                    ->first();

                $existingBrokerAccount = Account::where('company_id', $companyId)
                    ->where(function ($q) {
                        $q->where('table_name', 'brokers')
                          ->orWhere('hierarchy_path', 'like', '2-3-%');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                    ->first();

                if ($existingBroker) {
                    $updateData = [
                        'name' => $companyName,
                        'company_name' => $companyName,
                        'owner_name' => $ownerName,
                        'email' => $customer->email ?? $existingBroker->email,
                        'phone' => $customer->phone ?? $existingBroker->phone,
                        'address' => $customer->address ?? $existingBroker->address,
                        'ntn' => $customer->ntn ?? $existingBroker->ntn,
                        'stn' => $customer->stn ?? $existingBroker->stn,
                        'status' => $customer->status ?? $existingBroker->status,
                    ];
                    if (empty($existingBroker->account_id) && $existingBrokerAccount) {
                        $updateData['account_id'] = $existingBrokerAccount->id;
                    }
                    $existingBroker->update($updateData);
                } else {
                    if ($existingBrokerAccount && !Broker::where('account_id', $existingBrokerAccount->id)->exists()) {
                        $brokerAccountId = $existingBrokerAccount->id;
                    } else {
                        $accountName = $existingBrokerAccount ? "{$companyName} - {$ownerName}" : $companyName;
                        $Brokeraccount = Account::create(getParamsForAccountCreationByPath($companyId, $accountName, '2-3', 'brokers'));
                        $brokerAccountId = $Brokeraccount->id;
                    }

                    $brokerData = [
                        'company_id' => $customer->company_id ?? null,
                        'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                        'name' => $customer->company_name,
                        'company_name' => $customer->company_name,
                        'owner_name' => $ownerName,
                        'account_id' => $brokerAccountId,
                        'email' => $customer->email ?? null,
                        'phone' => $customer->phone ?? null,
                        'address' => $customer->address ?? null,
                        'ntn' => $customer->ntn ?? null,
                        'stn' => $customer->stn ?? null,
                        'status' => $customer->status,
                        'is_for_sales' => 1,
                    ];

                    Broker::create($brokerData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Customer created successfully.',
                'data' => [],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
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
            'consignees',
            'account.parent',
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

            $companyId = $request->company_id ?: ($customer->company_id ?: (auth()->user()?->current_company_id ?? auth()->user()?->company_id ?? 1));
            $companyName = trim($request->company_name);
            $ownerName = trim($request->owner_name);

            // Double check duplicate customer for same company: same company_name AND same owner_name (ignoring current customer)
            $duplicateCustomer = Customer::where('company_id', $companyId)
                ->where('id', '!=', $customer->id)
                ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
                ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)])
                ->exists();

            if ($duplicateCustomer) {
                return response()->json([
                    'error' => "A customer with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists.",
                    'errors' => ['company_name' => ["A customer with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists."]],
                ], 422);
            }

            $requestData['company_id'] = $companyId;
            $requestData['name'] = $companyName;
            $requestData['company_name'] = $companyName;
            $requestData['owner_name'] = $ownerName;

            if ($customer->account) {
                // Existing account update
                $customer->account->update([
                    'name' => $companyName,
                ]);
            } elseif (!empty($request->account_id)) {
                $requestData['account_id'] = $request->account_id;
            } else {
                // Check if unlinked COA exists before creating new
                $existingAccount = Account::where('company_id', $companyId)
                    ->where(function ($q) {
                        $q->where('table_name', 'customers')
                          ->orWhere('hierarchy_path', 'like', '1-5-%');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                    ->first();

                if ($existingAccount && !Customer::where('account_id', $existingAccount->id)->where('id', '!=', $customer->id)->exists()) {
                    $requestData['account_id'] = $existingAccount->id;
                } else {
                    $accountName = $existingAccount ? "{$companyName} - {$ownerName}" : $companyName;
                    $account = Account::create(getParamsForAccountCreationByPath($companyId, $accountName, '1-5', 'customers'));
                    $requestData['account_id'] = $account->id;
                }
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

            // Update consignees
            $this->updateConsignees(
                $customer,
                $request->consignee_id ?? [],
                $request->has('has_consignee') && $request->has_consignee ? ($request->consignee_name ?? []) : [],
                $request->consignee_address ?? [],
                $request->consignee_contact ?? [],
                $request->consignee_contact_person ?? [],
                $request->consignee_email ?? []
            );

            if ($request->has('create_as_broker')) {
                $existingBroker = $customer->broker ?? Broker::where('company_id', $companyId)
                    ->where(function ($q) use ($companyName, $ownerName) {
                        $q->where(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                        })
                        ->orWhere(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower($ownerName)]);
                        })
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)]);
                    })
                    ->first();

                $existingBrokerAccount = Account::where('company_id', $companyId)
                    ->where(function ($q) {
                        $q->where('table_name', 'brokers')
                          ->orWhere('hierarchy_path', 'like', '2-3-%');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($companyName)])
                    ->first();

                if ($existingBroker) {
                    $updateData = [
                        'name' => $companyName,
                        'company_name' => $companyName,
                        'owner_name' => $ownerName,
                        'email' => $customer->email ?? $existingBroker->email,
                        'phone' => $customer->phone ?? $existingBroker->phone,
                        'address' => $customer->address ?? $existingBroker->address,
                        'ntn' => $customer->ntn ?? $existingBroker->ntn,
                        'stn' => $customer->stn ?? $existingBroker->stn,
                        'status' => $customer->status ?? $existingBroker->status,
                    ];
                    if ($request->account_id) {
                        $updateData['account_id'] = $request->account_id;
                    } elseif (empty($existingBroker->account_id) && $existingBrokerAccount) {
                        $updateData['account_id'] = $existingBrokerAccount->id;
                    }
                    $existingBroker->update($updateData);
                    if ($existingBroker->account) {
                        $existingBroker->account->update(['name' => $companyName]);
                    }
                } else {
                    if ($existingBrokerAccount && !Broker::where('account_id', $existingBrokerAccount->id)->exists()) {
                        $brokerAccountId = $existingBrokerAccount->id;
                    } else {
                        $accountName = $existingBrokerAccount ? "{$companyName} - {$ownerName}" : $companyName;
                        $Brokeraccount = Account::create(getParamsForAccountCreationByPath($companyId, $accountName, '2-3', 'brokers'));
                        $brokerAccountId = $Brokeraccount->id;
                    }

                    $brokerData = [
                        'company_id' => $customer->company_id ?? null,
                        'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                        'name' => $customer->company_name,
                        'company_name' => $customer->company_name,
                        'owner_name' => $ownerName,
                        'account_id' => $request->account_id ?: $brokerAccountId,
                        'email' => $customer->email ?? null,
                        'phone' => $customer->phone ?? null,
                        'address' => $customer->address ?? null,
                        'ntn' => $customer->ntn ?? null,
                        'stn' => $customer->stn ?? null,
                        'status' => $customer->status,
                        'is_for_sales' => 1,
                    ];

                    Broker::create($brokerData);
                }
            } elseif ($customer->broker) {
                $customer->broker->delete();
            }

            DB::commit();

            return response()->json([
                'success' => 'Customer updated successfully.',
                'data' => [],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update Customer. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function updateConsignees($customer, $ids, $names, $addresses, $contacts, $contactPersons, $emails = [])
    {
        $existingConsignees = $customer->consignees()->get()->keyBy('id');
        $keptIds = [];

        foreach ($names as $index => $name) {
            if (empty($name)) {
                continue;
            }

            $payload = [
                'name' => $name,
                'address' => $addresses[$index] ?? '',
                'contact' => $contacts[$index] ?? '',
                'contact_person' => $contactPersons[$index] ?? '',
                'email' => $emails[$index] ?? '',
            ];

            $consigneeId = isset($ids[$index]) && $ids[$index] !== '' ? (int) $ids[$index] : null;

            if ($consigneeId && $existingConsignees->has($consigneeId)) {
                $existingConsignees[$consigneeId]->update($payload);
                $keptIds[] = $consigneeId;
            } else {
                $newConsignee = $customer->consignees()->create($payload);
                $keptIds[] = $newConsignee->id;
            }
        }

        $toDeleteIds = $existingConsignees->keys()->diff($keptIds)->values();

        if ($toDeleteIds->isEmpty()) {
            return;
        }

        $referencedIds = ExportOrder::withoutGlobalScopes()
            ->whereIn('consignee_id', $toDeleteIds)
            ->pluck('consignee_id')
            ->unique()
            ->all();

        $deletableIds = $toDeleteIds->diff($referencedIds)->values();

        if (!empty($referencedIds)) {
            $linkedConsigneeNames = $existingConsignees
                ->only($referencedIds)
                ->pluck('name')
                ->filter()
                ->implode(', ');

            throw new \RuntimeException(
                'Cannot remove consignee(s) already linked with Export Order: ' .
                ($linkedConsigneeNames ?: implode(', ', $referencedIds))
            );
        }

        if ($deletableIds->isNotEmpty()) {
            $customer->consignees()->whereIn('id', $deletableIds)->delete();
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

    public function importModal()
    {
        return view('management.master.customer.import_modal');
    }

    public function importRow(Request $request)
    {
        DB::beginTransaction();
        try {
            $rowData = $request->row_data;
            $meskeyCompanyId = $request->company_id ?: (auth()->user()?->current_company_id ?? auth()->user()?->company_id ?? 1);

            // Location Selection (Index 28, optional, pipe-separated names like Location A|Location B)
            $locationIds = null;
            $csvLocations = trim($rowData[28] ?? '');
            if (!empty($csvLocations)) {
                $locationNames = array_map('trim', explode('|', $csvLocations));
                $locationNames = array_filter($locationNames); // Remove empty values

                if (!empty($locationNames)) {
                    // Fetch all active locations to map names to IDs (Case-Insensitive)
                    $allLocations = CompanyLocation::where('status', 'active')->get();
                    $locationMap = [];
                    foreach ($allLocations as $loc) {
                        $locationMap[strtolower($loc->name)] = $loc->id;
                    }

                    $matchedIds = [];
                    foreach ($locationNames as $name) {
                        $lowerName = strtolower($name);
                        if (isset($locationMap[$lowerName])) {
                            $matchedIds[] = (string) $locationMap[$lowerName];
                        }
                    }

                    $locationIds = !empty($matchedIds) ? array_values(array_unique($matchedIds)) : null;
                }
            }

            if (empty($rowData) || count($rowData) < 4) {
                throw new \Exception("Insufficient data in row.");
            }

            // Cleanup & Format data
            $companyName = trim($rowData[0] ?? '');
            $ownerName = trim($rowData[1] ?? '');
            if ($ownerName === '') {
                $ownerName = $companyName;
            }

            $mobile = trim($rowData[2] ?? '');
            // Auto-fix leading zero if stripped by Excel (if 10 digits starting with 3, prepend 0)
            if (strlen($mobile) == 10 && str_starts_with($mobile, '3')) {
                $mobile = '0' . $mobile;
            }

            $ownerCnic = trim($rowData[3] ?? '');

            // Type Normalization (local or international, default local)
            $rawType = trim($rowData[4] ?? '');
            $type = !empty($rawType) ? strtolower(str_replace([' ', '-'], '_', $rawType)) : 'local';
            if (!in_array($type, ['local', 'international'])) {
                $type = 'local';
            }

            // Status is always active by default
            $status = 'active';

            $nextToKinMobile = trim($rowData[11] ?? '');
            if (strlen($nextToKinMobile) == 10 && str_starts_with($nextToKinMobile, '3')) {
                $nextToKinMobile = '0' . $nextToKinMobile;
            }

            // Mapping
            $data = [
                'company_id' => $meskeyCompanyId,
                'company_name' => $companyName,
                'name' => $companyName,
                'owner_name' => $ownerName,
                'owner_mobile_no' => $mobile,
                'owner_cnic_no' => $ownerCnic,
                'type' => $type,
                'status' => $status,
                'email' => !empty($rowData[5]) ? trim($rowData[5]) : null,
                'phone' => !empty($rowData[6]) ? trim($rowData[6]) : null,
                'address' => !empty($rowData[7]) ? trim($rowData[7]) : null,
                'ntn' => !empty($rowData[8]) ? trim($rowData[8]) : null,
                'stn' => !empty($rowData[9]) ? trim($rowData[9]) : null,
                'next_to_kin' => !empty($rowData[10]) ? trim($rowData[10]) : null,
                'next_to_kin_mobile_no' => !empty($nextToKinMobile) ? $nextToKinMobile : null,
                'create_as_broker' => (strtolower(trim($rowData[12] ?? '')) == 'yes'),
                'company_location_ids' => $locationIds,
            ];

            // Validation (Essential presence & format checks)
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'company_id' => 'required|exists:companies,id',
                'company_name' => 'required|string|max:255',
                'owner_name' => 'required',
                'owner_mobile_no' => 'required',
                'type' => 'required|in:local,international',
            ]);

            if ($validator->fails()) {
                throw new \Exception(implode(', ', $validator->errors()->all()));
            }

            // Strict duplicate customer check: same company_name AND same owner_name
            $duplicateCustomer = Customer::where('company_id', $meskeyCompanyId)
                ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim($companyName))])
                ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower(trim($ownerName))])
                ->first();

            if ($duplicateCustomer) {
                throw new \Exception("Duplicate Customer: A customer with Company Name '{$companyName}' and Owner Name '{$ownerName}' already exists.");
            }

            // Create new customer with COA Account (check for unlinked existing account first to avoid duplicates)
            $existingAccount = Account::where('company_id', $meskeyCompanyId)
                ->where(function ($q) {
                    $q->where('table_name', 'customers')
                      ->orWhere('hierarchy_path', 'like', '1-5-%');
                })
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($companyName))])
                ->first();

            if ($existingAccount && !Customer::where('account_id', $existingAccount->id)->exists()) {
                $data['account_id'] = $existingAccount->id;
            } else {
                $accountName = $existingAccount ? "{$companyName} - {$ownerName}" : $companyName;
                $account = Account::create(getParamsForAccountCreationByPath($meskeyCompanyId, $accountName, '1-5', 'customers'));
                $data['account_id'] = $account->id;
            }

            $data['unique_no'] = generateUniqueNumber('customers', null, null, 'unique_no');
            $customer = Customer::create($data);

            // Company Bank Details (Columns 13-17: Name, Branch, Code, Title, Account)
            if (!empty($rowData[13])) {
                $customer->companyBankDetails()->delete();
                CustomerCompanyBankDetail::create([
                    'customer_id' => $customer->id,
                    'bank_name' => trim($rowData[13]),
                    'branch_name' => trim($rowData[14] ?? ''),
                    'branch_code' => trim($rowData[15] ?? ''),
                    'account_title' => trim($rowData[16] ?? ''),
                    'account_number' => trim($rowData[17] ?? ''),
                ]);
            }

            // Owner Bank Details (Columns 18-22: Name, Branch, Code, Title, Account)
            if (!empty($rowData[18])) {
                $customer->ownerBankDetails()->delete();
                CustomerOwnerBankDetail::create([
                    'customer_id' => $customer->id,
                    'bank_name' => trim($rowData[18]),
                    'branch_name' => trim($rowData[19] ?? ''),
                    'branch_code' => trim($rowData[20] ?? ''),
                    'account_title' => trim($rowData[21] ?? ''),
                    'account_number' => trim($rowData[22] ?? ''),
                ]);
            }

            // Consignee Details (Columns 23-27: Name, Contact, Contact Person, Email, Address)
            if (!empty($rowData[23])) {
                $consigneeName = trim($rowData[23]);
                $consigneeData = [
                    'customer_id' => $customer->id,
                    'name' => $consigneeName,
                    'contact' => trim($rowData[24] ?? ''),
                    'contact_person' => trim($rowData[25] ?? ''),
                    'email' => trim($rowData[26] ?? ''),
                    'address' => trim($rowData[27] ?? ''),
                ];

                $existingConsignee = $customer->consignees()->where('name', $consigneeName)->first();
                if ($existingConsignee) {
                    $existingConsignee->update($consigneeData);
                } else {
                    CustomerConsignee::create($consigneeData);
                }
            }

            // Broker creation if requested
            if ($data['create_as_broker']) {
                $broker = Broker::where('company_id', $meskeyCompanyId)
                    ->where(function ($q) use ($companyName, $ownerName) {
                        $q->where(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim($companyName))])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower(trim($ownerName))]);
                        })
                        ->orWhere(function ($sq) use ($companyName, $ownerName) {
                            $sq->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($companyName))])
                               ->whereRaw('LOWER(TRIM(owner_name)) = ?', [strtolower(trim($ownerName))]);
                        })
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($companyName))]);
                    })
                    ->first();

                $existingBrokerAccount = Account::where('company_id', $meskeyCompanyId)
                    ->where(function ($q) {
                        $q->where('table_name', 'brokers')
                          ->orWhere('hierarchy_path', 'like', '2-3-%');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($companyName))])
                    ->first();

                if ($broker) {
                    $updateData = [
                        'name' => $companyName,
                        'company_name' => $companyName,
                        'owner_name' => $ownerName,
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                        'ntn' => $data['ntn'],
                        'stn' => $data['stn'],
                        'status' => $data['status'],
                    ];
                    if (empty($broker->account_id) && $existingBrokerAccount) {
                        $updateData['account_id'] = $existingBrokerAccount->id;
                    }
                    $broker->update($updateData);
                    if ($broker->account) {
                        $broker->account->update(['name' => $companyName]);
                    }
                } else {
                    if ($existingBrokerAccount && !Broker::where('account_id', $existingBrokerAccount->id)->exists()) {
                        $brokerAccountId = $existingBrokerAccount->id;
                    } else {
                        $accountName = $existingBrokerAccount ? "{$companyName} - {$ownerName}" : $companyName;
                        $Brokeraccount = Account::create(getParamsForAccountCreationByPath($meskeyCompanyId, $accountName, '2-3', 'brokers'));
                        $brokerAccountId = $Brokeraccount->id;
                    }

                    Broker::create([
                        'company_id' => $meskeyCompanyId,
                        'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                        'name' => $companyName,
                        'company_name' => $companyName,
                        'owner_name' => $ownerName,
                        'account_id' => $brokerAccountId,
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                        'ntn' => $data['ntn'],
                        'stn' => $data['stn'],
                        'status' => $data['status'],
                        'is_for_sales' => 1,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Imported successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
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
