<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\TicketContractRequest;
use App\Models\Arrival\ArrivalCustomSampling;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use App\Models\Master\QcReliefParameter;
use App\Models\Product;
use App\Models\PurchaseSamplingRequest;
use App\Models\User;

class TicketContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.ticket_contracts.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $tickets = ArrivalTicket::where('freight_status', 'completed')->orderBy('created_at', 'asc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.ticket_contracts.getList', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($request->ticket_id);

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->get();

        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $request->ticket_id)
            ->whereIn('approved_status', ['approved', 'rejected'])
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        return view('management.procurement.raw_material.ticket_contracts.create', compact('purchaseOrders', 'arrivalTicket', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketContractRequest $request)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);
        $arrivalTicket->update([
            'arrival_purchase_order_id' => $request->contract_id
        ]);

        return response()->json([
            'success' => 'Data stored successfully',
            'data' => $arrivalTicket,
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->get();

        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $id)
            ->whereIn('approved_status', ['approved', 'rejected'])
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        return view('management.procurement.raw_material.ticket_contracts.create', compact('purchaseOrders', 'arrivalTicket', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'));
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

    public function searchContracts(Request $request)
    {
        $searchTerm = $request->input('search');
        $initialLoad = $request->input('initial');
        $ticketId = $request->input('ticket_id');

        $query = ArrivalPurchaseOrder::with(['product', 'supplier', 'qcProduct'])
            ->where('freight_status', 'completed')
            ->orderBy('contract_date', 'desc');

        if ($initialLoad) {
            $query->limit(10);
        } elseif ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contract_no', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('qcProduct', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if ($ticketId) {
            $ticket = ArrivalTicket::find($ticketId);
            if ($ticket && $ticket->arrival_purchase_order_id) {
                $query->orWhere('id', $ticket->arrival_purchase_order_id);
            }
        }

        $contracts = $query->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'contract_no' => $contract->contract_no,
                    'contract_date_formatted' => $contract->contract_date->format('d-M-Y'),
                    'product' => $contract->product,
                    'qc_product_name' => $contract->qcProduct->name ?? 'N/A',
                    'supplier' => $contract->supplier,
                    'total_quantity' => number_format($contract->total_quantity),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $contracts
        ]);
    }
}
