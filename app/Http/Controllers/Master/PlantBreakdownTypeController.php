<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PlantBreakdownTypeRequest;
use App\Models\Master\PlantBreakdownType;
use Illuminate\Http\Request;

class PlantBreakdownTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.plant_breakdown_type.index');
    }

    /**
     * Get list of plant breakdown types.
     */
    public function getList(Request $request)
    {
        $plantBreakdownTypes = PlantBreakdownType::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                   ->orWhere('description', 'like', $searchTerm);
            });
        })
        ->when($request->filled('company_id'), function ($q) use ($request) {
            return $q->where('company_id', $request->company_id);
        })
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.master.plant_breakdown_type.getList', compact('plantBreakdownTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.plant_breakdown_type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlantBreakdownTypeRequest $request)
    {
        $data = $request->validated();
        $plantBreakdownType = PlantBreakdownType::create($request->all());

        return response()->json(['success' => 'Plant Breakdown Type created successfully.', 'data' => $plantBreakdownType], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $plantBreakdownType = PlantBreakdownType::findOrFail($id);
        return view('management.master.plant_breakdown_type.edit', compact('plantBreakdownType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlantBreakdownTypeRequest $request, PlantBreakdownType $plantBreakdownType)
    {
        $data = $request->validated();
        $plantBreakdownType->update($data);

        return response()->json(['success' => 'Plant Breakdown Type updated successfully.', 'data' => $plantBreakdownType], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlantBreakdownType $plantBreakdownType)
    {
        $plantBreakdownType->delete();
        return response()->json(['success' => 'Plant Breakdown Type deleted successfully.'], 200);
    }
}
