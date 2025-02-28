<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\{ProductSlab, ProductSlabType};
use Illuminate\Http\Request;
use App\Http\Requests\Master\ProductSlabRequest;

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
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.product_slab.getList', compact('ProductSlab'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $slab_types = ProductSlabType::where('status', 'active')->get();
        return view('management.master.product_slab.create', compact('slab_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductSlabRequest $request)
    {
        $data = $request->validated();
        $ProductSlabRequest = ProductSlab::create($request->all());

        return response()->json(['success' => 'Category created successfully.', 'data' => $ProductSlabRequest], 201);
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

public function getSlabsByProduct(Request $request)
{
    $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->sampling_request_id);

    // Check if related arrivalTicket exists
    if (!$arrivalSamplingRequest->arrivalTicket) {
        return response()->json(['success' => false, 'message' => 'Arrival ticket not found.'], 404);
    }

    $product_id = $arrivalSamplingRequest->arrivalTicket->product_id;
    $slabs = ProductSlab::where('product_id', $product_id)->get()->unique('product_slab_type_id');

    // Render view with the slabs wrapped inside a div
    $html = view('management.master.product_slab.forInspection', compact('slabs'))->render();

    return response()->json(['success' => true, 'html' => $html]);
}
}
