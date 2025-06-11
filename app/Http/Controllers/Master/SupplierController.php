<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SupplierRequest;
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

            // Generate unique number
            $requestData['unique_no'] = generateUniqueNumber('suppliers', null, null, 'unique_no');
            $requestData['name'] = $request->company_name;

            // Convert company locations to JSON
            $requestData['company_location_ids'] = $request->company_location_ids;

            // Create the supplier
            $supplier = Supplier::create($requestData);

            // Save company bank details - only if all required fields are present
            if (!empty($request->company_bank_name)) {
                $companyBankDetails = [];
                foreach ($request->company_bank_name as $key => $bankName) {
                    // Skip if bank name is empty (null or empty string)
                    if (empty($bankName)) {
                        continue;
                    }

                    $companyBankDetails= [
                        'bank_name' => $bankName,
                        'account_title' => $request->company_account_title[$key] ?? '',
                        'account_number' => $request->company_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id
                    ];
                     SupplierCompanyBankDetail::create($companyBankDetails);
 
                }

               
            }
            // Save owner bank details - only if all required fields are present
            if (!empty($request->owner_bank_name)) {
                $ownerBankDetails = [];
                foreach ($request->owner_bank_name as $key => $bankName) {
                    // Skip if bank name is empty (null or empty string)
                    if (empty($bankName)) {
                        continue;
                    }

                    $ownerBankDetails  = [
                        'bank_name' => $bankName,
                        'account_title' => $request->owner_account_title[$key] ?? '',
                        'account_number' => $request->owner_account_number[$key] ?? '',
                        'supplier_id' => $supplier->id
                    ];
                     SupplierOwnerBankDetail::create($ownerBankDetails);
                }

               
            }

            DB::commit();

            return response()->json([
                'success' => 'Supplier created successfully.',
                'data' => []
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Supplier creation failed: ' . $e->getMessage());

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
    dd();
    $companyLocations = CompanyLocation::all(); // Assuming you have a CompanyLocation model
    
    // Decode the JSON locations if needed
    $selectedLocations = json_decode($supplier->company_location_ids, true) ?? [];
    
    return view('management.master.supplier.edit', [
        'supplier' => $supplier,
        'companyLocations' => $companyLocations,
        'selectedLocations' => $selectedLocations
    ]);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, $id)
    {
        $data = $request->validated();
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());

        return response()->json(['success' => 'Category updated successfully.', 'data' => $supplier], 200);
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
