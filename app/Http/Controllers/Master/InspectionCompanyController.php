<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\InspectionCompanyRequest;
use App\Models\Master\InspectionCompany;
use Illuminate\Http\Request;

class InspectionCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.inspection-company.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $inspectionCompanies = InspectionCompany::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.inspection-company.getList', compact('inspectionCompanies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.inspection-company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InspectionCompanyRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();
        $fumigationCompany = InspectionCompany::create($request);

        return response()->json(['success' => 'Inspection Company created successfully.', 'data' => $fumigationCompany], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inspectionCompany = InspectionCompany::findOrFail($id);
        return view('management.master.inspection-company.edit', compact('inspectionCompany'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InspectionCompanyRequest $request, $id)
    {
        $data = $request->validated();
        $broker = InspectionCompany::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Inspection Company updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = InspectionCompany::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Inspection Company deleted successfully.'], 200);
    }
}
