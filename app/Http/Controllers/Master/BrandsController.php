<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Brands;
use App\Http\Requests\Master\BrandRequest;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
  
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.brands.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $brands = Brands::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.brands.getList', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrandRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();
        $fumigationCompany = Brands::create($request);

        return response()->json(['success' => 'Brand created successfully.', 'data' => $fumigationCompany], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $brand = Brands::findOrFail($id);
        return view('management.master.brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrandRequest $request, $id)
    {
        $data = $request->validated();
        $broker = Brands::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Brand updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $brand = Brands::findOrFail($id);

        $brand->delete();
        return response()->json(['success' => 'Brand deleted successfully.'], 200);
    }
}
