<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\PurchaseSamplingResultRequest;
use App\Models\Arrival\ArrivalCustomSampling;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use App\Models\Master\QcReliefParameter;
use App\Models\Product;
use App\Models\PurchaseSamplingRequest;
use App\Models\User;

class PurchaseSamplingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $isResampling = request()->route()->getName() === 'raw-material.purchase-resampling.index';
        return view('management.procurement.raw_material.purchase_sampling.index', compact('isResampling'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $isResampling = request()->route()->getName() === 'raw-material.get.purchase-resampling';

        $samplingRequests = PurchaseSamplingRequest::where('sampling_type', 'initial');

        if ($isResampling) {
            $samplingRequests->where('is_re_sampling', 'yes');
        } else {
            $samplingRequests->where('is_re_sampling', 'no');
        }

        $samplingRequests->where('is_done', 'no');

        $samplingRequests = $samplingRequests->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            $q->whereHas('arrivalTicket', function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm)
                    ->orWhere('supplier_name', 'like', $searchTerm);
            });
        })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            // ->where("is_done", "")
            ->orderByRaw("CASE WHEN is_done = 'no' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.purchase_sampling.getList', compact('samplingRequests', 'isResampling'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isResampling = request()->route()->getName() === 'raw-material.purchase-resampling.create';

        $query = PurchaseSamplingRequest::where('sampling_type', 'initial')->where('is_done', 'no');

        if ($isResampling) {
            $query->where('is_re_sampling', 'yes');
        } else {
            $query->where('is_re_sampling', 'no');
        }

        $samplingRequests = $query->get();
        $arrivalCustomSampling = ArrivalCustomSampling::all();
        $sampleTakenByUsers = User::all();
        $products = Product::all();

        return view('management.procurement.raw_material.purchase_sampling.create', compact('samplingRequests', 'isResampling', 'arrivalCustomSampling', 'sampleTakenByUsers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseSamplingResultRequest $request)
    {
        $PurchaseSamplingRequest = PurchaseSamplingRequest::findOrFail($request->purchase_sampling_request_id);
        $purchaseOrder = ArrivalPurchaseOrder::find($PurchaseSamplingRequest?->purchaseOrder?->id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'qc_product' => $request->arrival_product_id
            ]);
        }

        $PurchaseSamplingRequest->update([
            'remark' => $request->remarks,
            'is_done' => 'yes',
            'qc_product_id' => $request->arrival_product_id,
            'party_ref_no' => $request->party_ref_no ?? NULL,
            'sample_taken_by' => $request->sample_taken_by ?? NULL,
            'done_by' => auth()->user()->id,
        ]);

        if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
            $reliefParameters = QcReliefParameter::where('product_id', $request->arrival_product_id)
                ->where('parameter_type', 'slab')
                ->get()
                ->keyBy('slab_type_id');

            foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                $reliefDeduction = 0;

                if (isset($reliefParameters[$slabTypeId])) {
                    $reliefDeduction = $reliefParameters[$slabTypeId]->relief_percentage;
                }

                PurchaseSamplingResult::create([
                    'company_id' => $request->company_id,
                    'purchase_sampling_request_id' => $request->purchase_sampling_request_id,
                    'product_slab_type_id' => $slabTypeId,
                    'checklist_value' => $request->checklist_value[$key] ?? null,
                    'relief_deduction' => $reliefDeduction,
                ]);
            }
        }

        if (!empty($request->arrival_compulsory_qc_param_id) && !empty($request->compulsory_checklist_value)) {
            foreach ($request->arrival_compulsory_qc_param_id as $key => $paramId) {
                PurchaseSamplingResultForCompulsury::create([
                    'company_id' => $request->company_id,
                    'purchase_sampling_request_id' => $request->purchase_sampling_request_id,
                    'arrival_compulsory_qc_param_id' => $paramId,
                    'compulsory_checklist_value' => $request->compulsory_checklist_value[$key] ?? null,
                    'remark' => null,
                ]);
            }
        }

        return response()->json([
            'success' => 'Data stored successfully',
            'data' => [],
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $PurchaseSamplingRequest = PurchaseSamplingRequest::findOrFail($id);
        $authUserCompany = $request->company_id;

        if ($PurchaseSamplingRequest->is_done == 'no') {
            $isResampling = request()->route()->getName() === 'raw-material.purchase-resampling.edit';

            $query = PurchaseSamplingRequest::where('sampling_type', 'initial')->where('is_done', 'yes')->where('is_done', 'yes')->where('purchase_ticket_id', $PurchaseSamplingRequest->purchase_ticket_id)->where('is_resampling_made', 'yes');

            $samplingRequest = $query->latest()->first();
            $arrivalCustomSampling = ArrivalCustomSampling::all();
           // $sampleTakenByUsers = User::all();


                    $sampleTakenByUsers = User::role('QC')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
            $products = Product::all();
            $slabs = null;
            $results = [];
            $compulsuryResults = [];
            $isResamplingReq = null;

            if ($samplingRequest) {
                $isResamplingReq = true;

                $slabs = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)
                    ->get()
                    ->groupBy('product_slab_type_id')
                    ->map(function ($group) {
                        return $group->sortBy('from')->first();
                    });

                $results = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)->get();

                $results->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $compulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)->get();
            }

            return view('management.procurement.raw_material.purchase_sampling.create', compact('samplingRequest', 'isResamplingReq', 'isResampling', 'compulsuryResults', 'results', 'PurchaseSamplingRequest', 'arrivalCustomSampling', 'sampleTakenByUsers', 'products'));
        }

        $slabs = ProductSlab::where('product_id', $PurchaseSamplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $results = PurchaseSamplingResult::where('purchase_sampling_request_id', $PurchaseSamplingRequest->id)->get();

        $results->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            return $item;
        });

        $compulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $id)->get();

        $arrivalCustomSampling = ArrivalCustomSampling::all();
      //  $sampleTakenByUsers = User::all();
        $sampleTakenByUsers = User::role('QC')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
        return view('management.procurement.raw_material.purchase_sampling.edit', compact('PurchaseSamplingRequest', 'arrivalCustomSampling', 'compulsuryResults', 'sampleTakenByUsers', 'results', 'PurchaseSamplingRequest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {
        $purchaseOrder = ArrivalTicket::findOrFail($id);

        $data = $request->validated();
        $purchaseOrder->update($request->all());

        return response()->json(['success' => 'Ticket updated successfully.', 'data' => $purchaseOrder], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $purchaseOrder): JsonResponse
    {
        $purchaseOrder->delete();
        return response()->json(['success' => 'Ticket deleted successfully.'], 200);
    }

    public function updateStatus(Request $request)
    {

        $request->validate([
            'request_id' => 'required|exists:arrival_sampling_requests,id',
            'status' => 'required|in:approved,rejected,resampling'
        ]);

        $sampling = PurchaseSamplingRequest::find($request->request_id);

        if ($request->status == 'resampling') {

            PurchaseSamplingRequest::create([
                'company_id' => $sampling->company_id,
                'purchase_contract' => $sampling->purchase_contract,
                'sampling_type' => 'initial',
                'is_re_sampling' => 'yes',
                'is_done' => 'no',
                'remark' => null,
            ]);
            $sampling->is_resampling_made = 'yes';
        }



        $sampling->approved_status = $request->status;
        $sampling->save();


        //$sampling = PurchaseSamplingRequest::find($request->request_id);


        return response()->json(['message' => 'Request status updated successfully!']);
    }
}
