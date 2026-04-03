<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SupplierRequest;
use App\Models\Master\Account\Account;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\SupplierCompanyBankDetail;
use App\Models\SupplierOwnerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:raw-material-supplier', ['only' => ['index', 'edit', 'getList']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.supplier.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $Suppliers = Supplier::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                ->orWhere('company_name','like', $searchTerm);
            })->orWhere("owner_name", "like", $searchTerm);
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        // dd($Suppliers->first()->company_location_ids);
        return view('management.master.supplier.getList', compact('Suppliers'));
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

        return view('management.master.supplier.create', compact('companyLocation', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storebk(SupplierRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();

        $request['unique_no'] = generateUniqueNumber('suppliers', null, null, 'unique_no');
        $Supplier = Supplier::create($request);

        return response()->json(['success' => 'Supplier created successfully.', 'data' => $Supplier], 201);
    }

    public function store(SupplierRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('suppliers', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;
            $requestData['company_location_ids'] = $request->company_location_ids;
            $requestData['is_gate_buying_supplier'] = $request->is_gate_buying_supplier ?? 'No';
            $isSupplier = $requestData['is_gate_buying_supplier'] == 'Yes';

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-2', 'suppliers'));
                $requestData['account_id'] = $account->id;
            }

            $supplier = Supplier::create($requestData);

            if (! empty($request->company_bank_name)) {
                foreach ($request->company_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    SupplierCompanyBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->company_branch_name[$key] ?? '',
                        'branch_code' => $request->company_branch_code[$key] ?? '',
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id,
                    ]);
                }
            }

            if (! empty($request->owner_bank_name)) {
                foreach ($request->owner_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    SupplierOwnerBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->owner_branch_name[$key] ?? '',
                        'branch_code' => $request->owner_branch_code[$key] ?? '',
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id,
                    ]);
                }
            }

            if ($request->has('create_as_broker') && $request->create_as_broker) {

                $Brokeraccount = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-3', 'brokers'));

                $brokerData = [
                    'company_id' => $supplier->company_id ?? null,
                    'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                    'name' => $supplier->company_name,
                    'account_id' => $Brokeraccount->id,
                    'email' => $supplier->email ?? null,
                    'phone' => $supplier->phone ?? null,
                    'address' => $supplier->address ?? null,
                    'ntn' => $supplier->ntn ?? null,
                    'stn' => $supplier->stn ?? null,
                    'status' => $supplier->status,
                ];

                $broker = Broker::create($brokerData);
            }

            DB::commit();

            return response()->json([
                'success' => 'Supplier created successfully.',
                'data' => [],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create supplier. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $supplier = Supplier::with([
            'companyBankDetails',
            'ownerBankDetails',
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::all();
        $selectedLocations = $supplier->company_location_ids ?? [];
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Supplier')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.supplier.edit', [
            'supplier' => $supplier,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $requestData = $request->all();
            $requestData['is_gate_buying_supplier'] = $request->is_gate_buying_supplier ?? 'No';

            if ($supplier->account) {
                // Existing account update
                $supplier->account->update([
                    'name' => $request->company_name,
                ]);
            } elseif ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                // New account create
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-2', 'suppliers'));
                $requestData['account_id'] = $account->id;
            }

            $supplier->update($requestData);

            $this->updateBankDetails(
                $supplier,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'companyBankDetails'
            );

            $this->updateBankDetails(
                $supplier,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'ownerBankDetails'
            );

            if ($request->has('create_as_broker')) {
                $brokerData = [
                    'company_id' => $supplier->company_id ?? null,
                    'name' => $supplier->company_name,
                    'email' => $supplier->email ?? null,
                    'phone' => $supplier->phone ?? null,
                    'address' => $supplier->address ?? null,
                    'ntn' => $supplier->ntn ?? null,
                    'stn' => $supplier->stn ?? null,
                    'status' => $supplier->status,
                ];

                if ($supplier->broker) {
                    if ($request->account_id) {
                        $brokerData['account_id'] = $request->account_id;
                    } elseif (empty($supplier->broker->account_id)) {
                        $brokerData['account_id'] = $supplier->account_id;
                    }
                    $supplier->broker->update($brokerData);
                } else {
                    $brokerData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
                    $brokerData['account_id'] = $request->account_id ?: $supplier->account_id;
                    $supplier->broker()->create($brokerData);
                }
            } elseif ($supplier->broker) {
                $supplier->broker->delete();
            }

            DB::commit();

            return response()->json([
                'success' => 'Supplier updated successfully.',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update supplier. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function updateBankDetails($supplier, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $supplier->{$relation}->pluck('id')->toArray();
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
                $supplier->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $supplier->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (! empty($toDelete)) {
            $supplier->{$relation}()->whereIn('id', $toDelete)->delete();
        }
    }

    public function importModal()
    {
        return view('management.master.supplier.import_modal');
    }

    public function importRow(Request $request)
    {
        DB::beginTransaction();
        try {
            $rowData = $request->row_data;
            $meskeyCompanyId = $request->company_id; // Injected by CheckCurrentCompany middleware
            $locationIds = getUserCurrentCompanyLocations();

            if (empty($rowData) || count($rowData) < 6) {
                throw new \Exception("Insufficient data in row.");
            }

            // Cleanup & Format data
            $mobile = trim($rowData[2] ?? '');
            // Auto-fix leading zero if stripped by Excel (if 10 digits starting with 3, prepend 0)
            if (strlen($mobile) == 10 && str_starts_with($mobile, '3')) {
                $mobile = '0' . $mobile;
            }

            // Mapping
            $data = [
                'company_id' => $meskeyCompanyId,
                'company_name' => $rowData[0] ?? '',
                'owner_name' => $rowData[1] ?? '',
                'owner_mobile_no' => $mobile,
                'owner_cnic_no' => $rowData[3] ?? '',
                'type' => $rowData[4] ?? '',
                'status' => $rowData[5] ?? '',
                'email' => $rowData[6] ?? null,
                'phone' => $rowData[7] ?? null,
                'address' => $rowData[8] ?? null,
                'ntn' => $rowData[9] ?? null,
                'stn' => $rowData[10] ?? null,
                'create_as_broker' => (strtolower($rowData[11] ?? '') == 'yes'),
                'company_location_ids' => $locationIds,
            ];

            // Reduced Validation (Mainly presence checks)
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'company_id' => 'required|exists:companies,id',
                'company_name' => 'required|string|max:255',
                'owner_name' => 'required',
                'owner_mobile_no' => 'required',
                'owner_cnic_no' => 'required',
                'type' => 'required',
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                throw new \Exception(implode(', ', $validator->errors()->all()));
            }

            // check unique company name for this Meskey company
            $exists = Supplier::where('company_name', $data['company_name'])
                ->where('company_id', $meskeyCompanyId)
                ->exists();
            if ($exists) {
                throw new \Exception("Supplier company name already exists for this company.");
            }

            // Account Creation
            $account = Account::create(getParamsForAccountCreationByPath($meskeyCompanyId, $data['company_name'], '2-2', 'suppliers'));
            $data['account_id'] = $account->id;
            $data['unique_no'] = generateUniqueNumber('suppliers', null, null, 'unique_no');
            $data['name'] = $data['company_name'];

            $supplier = Supplier::create($data);

            // Company Bank Detail (Columns 12-16: Name, Branch, Code, Title, Acc)
            if (!empty($rowData[12])) {
                SupplierCompanyBankDetail::create([
                    'supplier_id' => $supplier->id,
                    'bank_name' => $rowData[12],
                    'branch_name' => $rowData[13] ?? '',
                    'branch_code' => $rowData[14] ?? '',
                    'account_title' => $rowData[15] ?? '',
                    'account_number' => $rowData[16] ?? '',
                ]);
            }

            // Owner Bank Detail (Columns 17-21: Name, Branch, Code, Title, Acc)
            if (!empty($rowData[17])) {
                SupplierOwnerBankDetail::create([
                    'supplier_id' => $supplier->id,
                    'bank_name' => $rowData[17],
                    'branch_name' => $rowData[18] ?? '',
                    'branch_code' => $rowData[19] ?? '',
                    'account_title' => $rowData[20] ?? '',
                    'account_number' => $rowData[21] ?? '',
                ]);
            }

            // Broker creation if requested
            if ($data['create_as_broker']) {
                $Brokeraccount = Account::create(getParamsForAccountCreationByPath($meskeyCompanyId, $data['company_name'], '2-3', 'brokers'));
                Broker::create([
                    'company_id' => $meskeyCompanyId,
                    'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                    'name' => $data['company_name'],
                    'account_id' => $Brokeraccount->id,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'ntn' => $data['ntn'],
                    'stn' => $data['stn'],
                    'status' => $data['status'],
                ]);
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
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }

    public function show($id)

    {
        $supplier = Supplier::with([
            'companyBankDetails',
            'ownerBankDetails',
        ])->findOrFail($id);

        return view('management.master.supplier.show', compact('supplier'));
    }
}
