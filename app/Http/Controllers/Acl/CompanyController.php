<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Models\Acl\{Company};
use Illuminate\Http\{Request, JsonResponse};
use App\Http\Requests\Company\{StoreCompanyRequest, UpdateCompanyRequest};



class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.acl.company.index');
    }
    public function getList(Request $request)
    {
        $companies = Company::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));
        return view('management.acl.company.getList', compact('companies'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.acl.company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
       // $data = $request->validated();
        $company = Company::create($request->all());
        return response()->json(['success' => 'Company created successfully.', 'data' => $company], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $company = Company::findorfail($id);
        return view('management.acl.company.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        //$data = $request->validated();
        $company->update($request->all());
        return response()->json(['success' => 'Company updated successfully.', 'data' => $company], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();
        return response()->json(['success' => 'Company deleted successfully.'], 200);
    }
    public function selectCompany(Request $request, $key = null)
    {
        if ($key) {
            $company = Company::where('app_key', $key)->firstOrFail();
            auth()
                ->user()
                ->update(['current_company_id' => $company->id]);
            return redirect('/');
        }
        $companies = Company::get();
        return view('management.acl.company.selectCompany', compact('companies'));
    }
}
