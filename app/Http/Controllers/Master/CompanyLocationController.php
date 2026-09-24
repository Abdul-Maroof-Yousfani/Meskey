<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductionPhase;
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
        $company_locations = CompanyLocation::with('city')
        ->when($request->filled('search'), function ($q) use ($request) {
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
        $cities = City::all(); 
        $production_phases = ProductionPhase::active()->get();
        return view('management.master.company_location.create', compact('cities', 'production_phases'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyLocationRequest $request)
    {
        $data = $request->validated();
        $data = $request->all();

        $data['truck_no_format'] = ($request->truck_no_format ?? 'off') == 'on' ? 1 : 0;
        $data['production_phases'] = $this->formatProductionPhases($request);

        $arrival_locations = CompanyLocation::create($data);

        return response()->json(['success' => 'Company Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cities = City::all(); 
        $company_location = CompanyLocation::findOrFail($id);
        $production_phases = ProductionPhase::active()->get();
        return view('management.master.company_location.edit', compact('company_location', 'cities', 'production_phases'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyLocationRequest $request, CompanyLocation $company_location)
    {
        $data = $request->validated();
        $data = $request->all();

        $data['truck_no_format'] = ($request->truck_no_format ?? 'off') == 'on' ? 1 : 0;
        $data['production_phases'] = $this->formatProductionPhases($request);

        $company_location->update($data);
        return response()->json(['success' => 'Company Location updated successfully.', 'data' => $company_location], 200);
    }

    /**
     * Normalize and format production phase IDs from request
     */
    protected function formatProductionPhases(Request $request): array
    {
        $inputPhases = $request->input('production_phases', []);
        if (!is_array($inputPhases)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $inputPhases))));
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
