<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UnitOfMeasureRequest;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class UnitOfMeasureController extends Controller
{
 /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.unit_of_measure.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $UnitOfMeasures = UnitOfMeasure::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.master.unit_of_measure.getList', compact('UnitOfMeasures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.unit_of_measure.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitOfMeasureRequest $request)
    {
        $data = $request->validated();
        $UnitOfMeasure = UnitOfMeasure::create($request->all());

        return response()->json(['success' => 'Category created successfully.', 'data' => $UnitOfMeasure], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $unit_of_measure = UnitOfMeasure::findOrFail($id);
        return view('management.master.unit_of_measure.edit', compact('unit_of_measure'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitOfMeasureRequest $request, UnitOfMeasure $unit_of_measure)
    {
        $data = $request->validated();
        $unit_of_measure->update($data);

        return response()->json(['success' => 'Category updated successfully.', 'data' => $unit_of_measure], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitOfMeasure $unit_of_measure): JsonResponse
    {
        $unit_of_measure->delete();
        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }
}
