<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\{ArrivalCustomSampling, ArrivalSamplingRequest, ArrivalSamplingResult, ArrivalSamplingResultForCompulsury};
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use App\Http\Requests\Arrival\ArrivalInitialSamplingResultRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\QcReliefParameter;
use App\Models\Product;
use App\Models\User;

class InitialSamplingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $isResampling = request()->route()->getName() === 'initial-resampling.index';
        return view('management.arrival.initial_sampling.index', compact('isResampling'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $isResampling = request()->route()->getName() === 'get.initial-resampling';

        $samplingRequests = ArrivalSamplingRequest::with('arrivalTicket')
            ->where('is_done', 'yes');

        if ($isResampling) {
            $samplingRequests->where('is_re_sampling', 'yes');
        }

        $samplingRequests = $samplingRequests->where('sampling_type', 'initial')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->whereHas('arrivalTicket', function ($sq) use ($searchTerm) {
                    $sq->where('unique_no', 'like', $searchTerm)
                        ->orWhere('supplier_name', 'like', $searchTerm);
                });
            })
             // Yahan relation through company_location_id check karen
        ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereHas('arrivalTicket', function ($sq) {
                $sq->where('location_id', auth()->user()->current_company_id);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.initial_sampling.getList', compact('samplingRequests', 'isResampling'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $isResampling = request()->route()->getName() === 'initial-resampling.create';
        $authUserCompany = $request->company_id;

        $query = ArrivalSamplingRequest::where('sampling_type', 'initial')->where('is_done', 'no');

        if ($isResampling) {
            $query->where('is_re_sampling', 'yes');
        } else {
            $query->where('is_re_sampling', 'no');
        }

        $samplingRequests = $query
        
         ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereHas('arrivalTicket', function ($sq) {
                $sq->where('location_id', auth()->user()->current_company_id);
            });
        })
        ->get();
        $arrivalCustomSampling = ArrivalCustomSampling::all();

        $sampleTakenByUsers = User::role('QC')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $products = Product::all();

        return view('management.arrival.initial_sampling.create', compact('samplingRequests', 'isResampling', 'arrivalCustomSampling', 'sampleTakenByUsers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalInitialSamplingResultRequest $request)
    {
        $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->arrival_sampling_request_id);

        if ($ArrivalSamplingRequest->is_done === 'yes') {
            return response('This sampling request has already been processed.', 422);
        }

        $arrivalTicket = ArrivalTicket::findOrFail($ArrivalSamplingRequest->arrival_ticket_id);

        $arrivalTicket->update([
            'qc_product' => $request->arrival_product_id
        ]);

        $ArrivalSamplingRequest->update([
            'remark' => $request->remarks,
            'arrival_product_id' => $request->arrival_product_id,
            'is_done' => 'yes',
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

                ArrivalSamplingResult::create([
                    'company_id' => $request->company_id,
                    'arrival_sampling_request_id' => $request->arrival_sampling_request_id,
                    'product_slab_type_id' => $slabTypeId,
                    'checklist_value' => $request->checklist_value[$key] ?? null,
                    'relief_deduction' => $reliefDeduction,
                ]);
            }
        }

        if (!empty($request->arrival_compulsory_qc_param_id) && !empty($request->compulsory_checklist_value)) {
            foreach ($request->arrival_compulsory_qc_param_id as $key => $paramId) {
                ArrivalSamplingResultForCompulsury::create([
                    'company_id' => $request->company_id,
                    'arrival_sampling_request_id' => $request->arrival_sampling_request_id,
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
    public function edit($id)
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->get();
        $products = Product::all();

        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);

        $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();

        $results->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            return $item;
        });

        $compulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();

        $arrivalCustomSampling = ArrivalCustomSampling::all();
        $sampleTakenByUsers = User::all();

        return view('management.arrival.initial_sampling.edit', compact('samplingRequests', 'products', 'arrivalCustomSampling', 'compulsuryResults', 'sampleTakenByUsers', 'results', 'arrivalSamplingRequest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);

        $data = $request->validated();
        $arrivalTicket->update($request->all());

        return response()->json(['success' => 'Ticket updated successfully.', 'data' => $arrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        $arrivalTicket->delete();
        return response()->json(['success' => 'Ticket deleted successfully.'], 200);
    }

    public function updateStatus(Request $request)
    {

        $request->validate([
            'request_id' => 'required|exists:arrival_sampling_requests,id',
            'status' => 'required|in:approved,rejected,resampling'
        ]);

        $sampling = ArrivalSamplingRequest::find($request->request_id);

        if ($request->status == 'resampling') {

            ArrivalSamplingRequest::create([
                'company_id' => $sampling->company_id,
                'arrival_ticket_id' => $sampling->arrival_ticket_id,
                'sampling_type' => 'initial',
                'is_re_sampling' => 'yes',
                'is_done' => 'no',
                'remark' => null,
            ]);
            $sampling->is_resampling_made = 'yes';
        }



        $sampling->approved_status = $request->status;
        $sampling->save();


        //$sampling = ArrivalSamplingRequest::find($request->request_id);


        return response()->json(['message' => 'Request status updated successfully!']);
    }
}
