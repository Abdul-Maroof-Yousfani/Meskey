<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\{ProductSlab, ProductSlabType};
use App\Models\Master\ArrivalCompulsoryQcParam;
use Illuminate\Http\Request;
use App\Http\Requests\Master\ProductSlabRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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
        $productIds = ProductSlab::select('product_id')
            ->when($request->filled('product_id'), function ($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('product_slab_type_id'), function ($q) use ($request) {
                return $q->where('product_slab_type_id', $request->product_slab_type_id);
            })
            ->where('company_id', $request->company_id)
            ->groupBy(['product_id', 'created_at'])
            ->latest()
            ->paginate(request('per_page', 25));

        $productSlabs = ProductSlab::with(['product', 'slabType'])
            ->whereIn('product_id', $productIds->pluck('product_id'))
            ->get()
            ->groupBy('product_id');

        return view('management.master.product_slab.getList', [
            'productSlabs' => $productSlabs,
            'paginator' => $productIds
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     $slab_types = ProductSlabType::where('status', 'active')->get();
    //     return view('management.master.product_slab.create', compact('slab_types'));
    // }

    // Add this to your ProductSlabController
    public function create()
    {
        $slab_types = ProductSlabType::where('status', 'active')->get();
        return view('management.master.product_slab.create', compact('slab_types'));
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'slabs' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            ProductSlab::where('product_id', $request->product_id)->delete();

            foreach ($request->slabs as $slabTypeId => $slabData) {
                if (isset($slabData['is_enabled']) && $slabData['is_enabled'] == 1) {
                    $deductionType = $slabData['deduction_type'] ?? 'kg';
                    $isTiered = ($slabData['is_tiered'] ?? 'on') == 'off' ? 0 : 1;

                    if (isset($slabData['ranges'])) {
                        $validRanges = collect($slabData['ranges'])
                            ->filter(function ($range) {
                                return !is_null($range['from'] ?? null) &&
                                    !is_null($range['to'] ?? null) &&
                                    !is_null($range['deduction_value'] ?? null);
                            })
                            ->sortBy('from')
                            ->values()
                            ->all();

                        foreach ($validRanges as $range) {
                            if ($range['from'] >= $range['to']) {
                                throw new \Exception("Invalid range: 'From' value must be less than 'To' value for slab type $slabTypeId");
                            }

                            ProductSlab::create([
                                'company_id' => $request->company_id,
                                'product_id' => $request->product_id,
                                'product_slab_type_id' => $slabTypeId,
                                'from' => $range['from'],
                                'to' => $range['to'],
                                'deduction_type' => $deductionType,
                                'is_tiered' => $isTiered,
                                'deduction_value' => $range['deduction_value'],
                                'is_enabled' => true,
                                'status' => 'active'
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Product slabs created successfully.',
                'redirect' => route('get.product-slab')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create product slabs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateMultiple(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'slabs' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            ProductSlab::where('product_id', $request->product_id)->delete();

            foreach ($request->slabs as $slabTypeId => $slabData) {
                if (isset($slabData['is_enabled']) && $slabData['is_enabled'] == 1) {
                    $deductionType = $slabData['deduction_type'] ?? 'kg';
                    $isTiered = ($slabData['is_tiered'] ?? 'off') == 'on' ? 1 : 0;

                    if (isset($slabData['ranges'])) {
                        $validRanges = collect($slabData['ranges'])
                            ->filter(function ($range) {
                                return !is_null($range['from'] ?? null) &&
                                    !is_null($range['to'] ?? null) &&
                                    !is_null($range['deduction_value'] ?? null);
                            })
                            ->sortBy('from')
                            ->values()
                            ->all();

                        foreach ($validRanges as $range) {
                            if ($range['from'] >= $range['to']) {
                                throw new \Exception("Invalid range: 'From' value must be less than 'To' value for slab type $slabTypeId");
                            }

                            ProductSlab::create([
                                'company_id' => $request->company_id,
                                'product_id' => $request->product_id,
                                'product_slab_type_id' => $slabTypeId,
                                'from' => $range['from'],
                                'to' => $range['to'],
                                'is_tiered' => $isTiered,
                                'deduction_type' => $deductionType,
                                'deduction_value' => $range['deduction_value'],
                                'is_enabled' => true,
                                'status' => 'active'
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Product slabs updated successfully.',
                'redirect' => route('get.product-slab')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update product slabs: ' . $e->getMessage()
            ], 500);
        }
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
    // public function edit($id)
    // {
    //     $unit_of_measure = ProductSlab::findOrFail($id);
    //     return view('management.master.product_slab.edit', compact('unit_of_measure'));
    // }

    public function edit($productId)
    {
        $product = Product::findOrFail($productId);
        $slab_types = ProductSlabType::where('status', 'active')->get();
        $productSlabs = ProductSlab::where('product_id', $productId)->get();

        return view('management.master.product_slab.edit', compact('product', 'slab_types', 'productSlabs'));
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
    public function destroy(ProductSlab $unit_of_measure)
    {
        $unit_of_measure->delete();
        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }

    public function destroyMultiple($productId)
    {
        ProductSlab::where('product_id', $productId)->delete();
        return response()->json(['success' => 'Product slabs deleted successfully.'], 200);
    }

    public function getSlabsByProduct(Request $request)
    {
        if (isset($request->product_id)) {
            $initialRequestForInnerReq = null;
            $initialRequestResults = null;
            $initialRequestCompulsuryResults = null;
            $isInner = $request->isInner ?? false;

            if (isset($request->ticket_id)) {
                $initialRequestForInnerReq = ArrivalSamplingRequest::where('arrival_ticket_id', $request->ticket_id)
                    ->where('approved_status', '!=', 'pending')
                    ->latest()
                    ->first();
                if ($initialRequestForInnerReq) {
                    $initialRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
                    $initialRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
                }
            }

            $compulsoryParams = ArrivalCompulsoryQcParam::get();
            $slabs = ProductSlab::where('product_id', $request->product_id)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($group) {
                    $minFrom = $group->sortBy('from')->first();
                    $minFrom->max_range = $minFrom->to;
                    return $minFrom;
                })
                ->values();

            $slabs = $slabs->map(function ($slab) use ($initialRequestResults) {
                $slab->checklist_value = null;

                if ($initialRequestResults) {
                    $matchingResult = $initialRequestResults->firstWhere('product_slab_type_id', $slab->product_slab_type_id);
                    if ($matchingResult) {
                        $slab->checklist_value = $matchingResult->checklist_value;
                    }
                }

                return $slab;
            });

            $compulsoryParams = $compulsoryParams->map(function ($param) use ($initialRequestCompulsuryResults) {
                $param->checklist_value = null;

                if ($initialRequestCompulsuryResults) {
                    $matchingResult = $initialRequestCompulsuryResults->firstWhere('arrival_compulsory_qc_param_id', $param->id);
                    if ($matchingResult) {
                        $param->checklist_value = $matchingResult->compulsory_checklist_value;
                    }
                }

                return $param;
            });

            $html = view('management.master.product_slab.forInspection', compact(
                'slabs',
                'compulsoryParams',
                'initialRequestForInnerReq',
                'initialRequestResults',
                'initialRequestCompulsuryResults',
                'isInner'
            ))->render();

            return response()->json(['success' => true, 'html' => $html]);
        }

        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->sampling_request_id);
        $compulsoryParams  = ArrivalCompulsoryQcParam::get();

        // Check if related arrivalTicket exists
        if (!$arrivalSamplingRequest->arrivalTicket) {
            return response()->json(['success' => false, 'message' => 'Arrival ticket not found.'], 404);
        }

        $product_id = $arrivalSamplingRequest->arrivalTicket->product_id;
        $slabs = ProductSlab::where('product_id', $product_id)->get()->unique('product_slab_type_id');

        // Render view with the slabs wrapped inside a div
        $html = view('management.master.product_slab.forInspection', compact('slabs', 'compulsoryParams'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
