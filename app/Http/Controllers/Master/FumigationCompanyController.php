<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Http\Requests\Master\FumigationCompanyRequest;
use App\Models\Master\FumigationCompany;
use Illuminate\Http\Request;

class FumigationCompanyController extends Controller
{
   /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.fumigation-company.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $fumigationCompanies = FumigationCompany::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.fumigation-company.getList', compact('fumigationCompanies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.fumigation-company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FumigationCompanyRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();
        $fumigationCompany = FumigationCompany::create($request);

        return response()->json(['success' => 'Fumigation Company created successfully.', 'data' => $fumigationCompany], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $fumigationCompany = FumigationCompany::findOrFail($id);
        return view('management.master.fumigation-company.edit', compact('fumigationCompany'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FumigationCompanyRequest $request, $id)
    {
        $data = $request->validated();
        $broker = FumigationCompany::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Fumigation Company updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = FumigationCompany::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Fumigation Company deleted successfully.'], 200);
    }
}
