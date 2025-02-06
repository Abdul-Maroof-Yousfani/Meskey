<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;

class ProductSlabController extends Controller
{
 /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.product_slab.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $ProductSlab = ProductSlab::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.master.product_slab.getList', compact('ProductSlab'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.product_slab.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitOfMeasureRequest $request)
    {
        $data = $request->validated();
        $UnitOfMeasure = ProductSlab::create($request->all());

        return response()->json(['success' => 'Category created successfully.', 'data' => $UnitOfMeasure], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $unit_of_measure = ProductSlab::findOrFail($id);
        return view('management.master.product_slab.edit', compact('unit_of_measure'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitOfMeasureRequest $request, ProductSlab $unit_of_measure)
    {
        $data = $request->validated();
        $unit_of_measure->update($data);

        return response()->json(['success' => 'Category updated successfully.', 'data' => $unit_of_measure], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductSlab $unit_of_measure): JsonResponse
    {
        $unit_of_measure->delete();
        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }
}
