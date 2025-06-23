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
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        //dd($Suppliers->first()->company_location_ids);
        return view('management.master.supplier.getList', compact('Suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocation = CompanyLocation::where('status', 'active')->get();
        return view('management.master.supplier.create', compact('companyLocation'));
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

            $account = Account::create(getParamsForAccountCreation($request->company_id, $request->company_name, 'Supplier'));
            $supplier = Supplier::create($requestData);

            if (!empty($request->company_bank_name)) {
                foreach ($request->company_bank_name as $key => $bankName) {
                    if (empty($bankName)) continue;

                    SupplierCompanyBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->company_branch_name[$key] ?? '',
                        'branch_code' => $request->company_branch_code[$key] ?? '',
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id
                    ]);
                }
            }

            // Save owner bank details
            if (!empty($request->owner_bank_name)) {
                foreach ($request->owner_bank_name as $key => $bankName) {
                    if (empty($bankName)) continue;

                    SupplierOwnerBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->owner_branch_name[$key] ?? '',
                        'branch_code' => $request->owner_branch_code[$key] ?? '',
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id
                    ]);
                }
            }

            if ($request->has('create_as_broker') && $request->create_as_broker) {
                $account = Account::create(getParamsForAccountCreation($request->company_id, $request->company_name, 'Broker'));

                $brokerData = [
                    'company_id' => $supplier->company_id ?? null,
                    'unique_no' => generateUniqueNumber('brokers', null, null, 'unique_no'),
                    'name' => $supplier->company_name,
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
                'data' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error('Supplier creation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to create supplier. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
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
            'ownerBankDetails'
        ])->findOrFail($id);
        // dd($supplier->company_location_ids);
        $companyLocations = CompanyLocation::all(); // Assuming you have a CompanyLocation model

        // Decode the JSON locations if needed
        $selectedLocations = $supplier->company_location_ids ?? [];

        return view('management.master.supplier.edit', [
            'supplier' => $supplier,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations
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
                    $supplier->broker->update($brokerData);
                } else {
                    $brokerData['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
                    $supplier->broker()->create($brokerData);
                }
            } elseif ($supplier->broker) {
                $supplier->broker->delete();
            }

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
    }

    protected function updateBankDetails($supplier, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $supplier->{$relation}->pluck('id')->toArray();
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
                $supplier->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $supplier->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            $supplier->{$relation}()->whereIn('id', $toDelete)->delete();
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
}
