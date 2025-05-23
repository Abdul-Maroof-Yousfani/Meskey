<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use Illuminate\Http\Request;
use App\Http\Requests\Master\CompanyLocationRequest;

class CompanyLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.company_location.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $company_locations = CompanyLocation::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                    ->orWhere('code', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.company_location.getList', compact('company_locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.company_location.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyLocationRequest $request)
    {
        $data = $request->validated();
        $arrival_locations = CompanyLocation::create($request->all());

        return response()->json(['success' => 'Company Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $company_location = CompanyLocation::findOrFail($id);
        return view('management.master.company_location.edit', compact('company_location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyLocationRequest $request, CompanyLocation $company_location)
    {
        $data = $request->validated();
        $company_location->update($data);
        return response()->json(['success' => 'Company Location updated successfully.', 'data' => $company_location], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $company_location = CompanyLocation::findOrFail($id);
        $company_location->delete();
        return response()->json(['success' => 'Company Location deleted successfully.'], 200);
    }
}
