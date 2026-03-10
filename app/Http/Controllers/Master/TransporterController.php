<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\TransporterRequest;
use App\Models\Master\Account\Account;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Transporter;
use App\Models\TransporterCompanyBankDetail;
use App\Models\TransporterOwnerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransporterController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:transporter', ['only' => ['index', 'edit', 'getList']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.transporter.index');
    }

    /**
     * Get list of transporters.
     */
    public function getList(Request $request)
    {
        $Transporters = Transporter::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                ->orWhere('company_name','like', $searchTerm);
            })->orWhere("owner_name", "like", $searchTerm);
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.transporter.getList', compact('Transporters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocation = CompanyLocation::where('status', 'active')->get();
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Transporter')
                ->orWhere('name', 'Supplier')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.transporter.create', compact('companyLocation', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransporterRequest $request)
    {
        DB::beginTransaction();

        try {
            $requestData = $request->all();

            $requestData['unique_no'] = generateUniqueNumber('transporters', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;
            $requestData['company_location_ids'] = $request->company_location_ids;

            if ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                // Using 2-5 for Transporters as 2-4 is Vendors
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-5', 'transporters'));
                $requestData['account_id'] = $account->id;
            }

            $transporter = Transporter::create($requestData);

            if (! empty($request->company_bank_name)) {
                foreach ($request->company_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    TransporterCompanyBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->company_branch_name[$key] ?? '',
                        'branch_code' => $request->company_branch_code[$key] ?? '',
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'transporter_id' => $transporter->id,
                    ]);
                }
            }

            if (! empty($request->owner_bank_name)) {
                foreach ($request->owner_bank_name as $key => $bankName) {
                    if (empty($bankName)) {
                        continue;
                    }

                    TransporterOwnerBankDetail::create([
                        'bank_name' => $bankName,
                        'branch_name' => $request->owner_branch_name[$key] ?? '',
                        'branch_code' => $request->owner_branch_code[$key] ?? '',
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'transporter_id' => $transporter->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Transporter created successfully.',
                'data' => [],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create transporter. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $transporter = Transporter::with([
            'companyBankDetails',
            'ownerBankDetails',
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::all();
        $selectedLocations = $transporter->company_location_ids ?? [];
        $accounts = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Transporter')
                ->orWhere('name', 'Supplier')
                ->orWhere('name', 'Broker');
        })->get();

        return view('management.master.transporter.edit', [
            'transporter' => $transporter,
            'companyLocations' => $companyLocations,
            'selectedLocations' => $selectedLocations,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransporterRequest $request, Transporter $transporter)
    {
        DB::beginTransaction();

        try {
            $requestData = $request->all();

            if ($transporter->account) {
                $transporter->account->update([
                    'name' => $request->company_name,
                ]);
            } elseif ($request->account_id) {
                $requestData['account_id'] = $request->account_id;
            } else {
                $account = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->company_name, '2-5', 'transporters'));
                $requestData['account_id'] = $account->id;
            }

            $transporter->update($requestData);

            $this->updateBankDetails(
                $transporter,
                $request->company_bank_name ?? [],
                $request->company_branch_name ?? [],
                $request->company_branch_code ?? [],
                $request->company_account_title ?? [],
                $request->company_account_number ?? [],
                'companyBankDetails'
            );

            $this->updateBankDetails(
                $transporter,
                $request->owner_bank_name ?? [],
                $request->owner_branch_name ?? [],
                $request->owner_branch_code ?? [],
                $request->owner_account_title ?? [],
                $request->owner_account_number ?? [],
                'ownerBankDetails'
            );

            DB::commit();

            return response()->json([
                'success' => 'Transporter updated successfully.',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update transporter. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function updateBankDetails($transporter, $bankNames, $branchNames, $branchCodes, $accountTitles, $accountNumbers, $relation)
    {
        $existingIds = $transporter->{$relation}->pluck('id')->toArray();
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
                $transporter->{$relation}()->where('id', $existingIds[$index])->update($bankData);
                $updatedIds[] = $existingIds[$index];
            } else {
                $transporter->{$relation}()->create($bankData);
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (! empty($toDelete)) {
            $transporter->{$relation}()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transporter $transporter)
    {
        $transporter->delete();

        return response()->json(['success' => 'Transporter deleted successfully.'], 200);
    }
}
