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
        $ProductSlab = ProductSlab::when($request->filled('product_id'), function ($q) use ($request) {
         
            return $q->where(function ($sq) use ($request) {
                $sq->where('product_id', $request->product_id)->where('product_slab_type_id', $request->product_slab_type_id);
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
    public function destroy(ProductSlab $unit_of_measure)
    {
        $unit_of_measure->delete();
        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }

    public function getSlabsByProduct(Request $request)
    {
        if (isset($request->product_id)) {
            $initialRequestForInnerReq = null;
            $initialRequestResults = null;
            $initialRequestCompulsuryResults = null;
            $isInner = $request->isInner ?? false;

            if (isset($request->ticket_id)) {
                $initialRequestForInnerReq = ArrivalSamplingRequest::where('sampling_type', 'initial')
                    ->where('arrival_ticket_id', $request->ticket_id)
                    ->where('approved_status', 'approved')
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
