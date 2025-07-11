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
use Illuminate\Support\Facades\DB;

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
        $tickets = ArrivalTicket::where('freight_status', 'completed')->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.ticket_contracts.getList', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($request->ticket_id);

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->where('status', 'pending')->get();

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
    public function store(Request $request)
    {
        $request->validate([
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'selected_contract' => 'required|exists:arrival_purchase_orders,id',
            'closing_trucks_qty' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $arrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);
            $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->selected_contract);

            $arrivalTicket->update([
                'arrival_purchase_order_id' => $request->selected_contract,
                // 'closing_trucks_qty' => $request->closing_trucks_qty
            ]);

            $arrivalTicket->increment('closing_trucks_qty', $request->closing_trucks_qty);
            // $purchaseOrder->increment('arrived_quantity', $arrivalTicket->net_weight);
            // $purchaseOrder->decrement('remaining_quantity', $arrivalTicket->net_weight);

            if ($request->mark_completed || $purchaseOrder->remaining_quantity <= 0) {
                $purchaseOrder->update([
                    'completed_at' => now()
                ]);
            }

            if ($request->mark_completed == 1) {
                $purchaseOrder->update([
                    'status' => 'completed',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' =>  'Ticket successfully linked to contract',
                'redirect' => route('raw-material.ticket-contracts.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to link ticket to contract: ' . $e->getMessage()
            ], 500);
        }
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

        return response()->json(['message' => 'Request status updated successfully!']);
    }

    public function searchContracts(Request $request)
    {
        $ticket = ArrivalTicket::find($request->ticket_id);
        $query = ArrivalPurchaseOrder::with(['supplier', 'product', 'totalLoadingWeight'])
            ->where('status', 'draft');

        if ($ticket?->sauda_type_id) {
            $query->where('sauda_type_id', $ticket->sauda_type_id);
        }

        if ($request->ticket_id) {
            $linkedId = $ticket?->arrival_purchase_order_id;
            if ($linkedId) $query->where('id', $linkedId);
        }

        if ($request->initial) {
            $query->limit(10);
        } elseif ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_no', 'like', "%$search%")
                    ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%$search%"))
                    ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%$search%"));
            });
        }

        $contracts = $query->orderBy('created_at', 'desc')->get()->map(fn($c) => [
            'id' => $c->id,
            'contract_no' => $c->contract_no,
            'qc_product_name' => $c->product->name,
            'supplier' => $c->supplier,
            'total_quantity' => $c->total_quantity,
            'min_quantity' => $c->min_quantity,
            'max_quantity' => $c->max_quantity,
            'remaining_quantity' => $c->remaining_quantity,
            'arrived_quantity' => $c->arrived_quantity,
            'truck_no' => $c->truck_no,
            'trucks_arrived' => $c->trucks_arrived,
            'no_of_trucks' => $c->no_of_trucks,
            'status' => $c->status ?: 'N/A',
            'contract_date_formatted' => $c->created_at->format('d-M-Y'),
            'total_loading_weight' => $c->totalLoadingWeight->total_loading_weight ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $contracts]);
    }
}
