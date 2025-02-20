<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ProductSlabType;
use App\Http\Requests\Master\ProductSlabTypeRequest;
use Illuminate\Http\Request;

class ProductSlabTypeController extends Controller
{
 /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.product_slab_type.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $product_slab_types = ProductSlabType::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
                ->where('company_id',$request->company_id)

        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.master.product_slab_type.getList', compact('product_slab_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.product_slab_type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductSlabTypeRequest $request)
    {
        $data = $request->validated();
        $product_slab_type = ProductSlabType::create($request->all());

        return response()->json(['success' => 'Product Slab Type created successfully.', 'data' => $product_slab_type], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product_slab_type = ProductSlabType::findOrFail($id);
        return view('management.master.product_slab_type.edit', compact('product_slab_type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductSlabTypeRequest $request, ProductSlabType $product_slab_type)
    {
        $data = $request->validated();
        $product_slab_type->update($data);

        return response()->json(['success' => 'Product Slab Type updated successfully.', 'data' => $product_slab_type], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
                $product_slab_type = ProductSlabType::findOrFail($id);

        $product_slab_type->delete();
        return response()->json(['success' => 'Product Slab Type deleted successfully.'], 200);
    }
}
