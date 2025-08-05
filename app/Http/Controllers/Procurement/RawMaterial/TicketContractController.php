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
use App\Models\Master\Account\Transaction;
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
        $tickets = ArrivalTicket::select('arrival_tickets.*', 'grn_numbers.unique_no as grn_unique_no')
            ->leftJoin('arrival_slips', 'arrival_tickets.id', '=', 'arrival_slips.arrival_ticket_id')
            ->leftJoin('grn_numbers', function ($join) {
                $join->on('arrival_slips.id', '=', 'grn_numbers.model_id')
                    ->where('grn_numbers.model_type', 'arrival-slip');
            })
            ->where(function ($query) {
                $query->where('arrival_tickets.freight_status', 'completed')
                    ->orWhere('arrival_tickets.first_qc_status', 'rejected');
            })
            ->orderBy('arrival_tickets.created_at', 'desc')
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

            $amount = $arrivalTicket->arrived_net_weight * $purchaseOrder->rate_per_kg;
            $truckNo = $arrivalTicket->truck_no ?? 'N/A';
            $biltyNo = $arrivalTicket->bilty_no ?? 'N/A';

            $updateData = [
                'arrival_purchase_order_id' => $request->selected_contract,
                'closing_trucks_qty' => $request->closing_trucks_qty
            ];

            if ($request->verify_ticket) {
                $updateData['is_ticket_verified'] = 1;
                $updateData['ticket_verified_by'] = auth()->user()->id;
                $updateData['ticket_verified_at'] = now();
            } else {
                $updateData['is_ticket_verified'] = 0;
                // $updateData['ticket_verified_by'] = auth()->user()->id;
                // $updateData['ticket_verified_at'] = now();
            }

            $arrivalTicket->update($updateData);

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

            $uniqueNo = $arrivalTicket->arrivalSlip->unique_no ?? '';
            $referenceNo = "$truckNo/$biltyNo";

            $paymentDetails = calculatePaymentDetails($arrivalTicket->id, 1);
            $contractNo = $arrivalTicket->purchaseOrder->contract_no ?? 'N/A';
            $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;
            $supplierNetAmount = $paymentDetails['calculations']['supplier_net_amount'] ?? 0;
            $qcAccountId = $arrivalTicket->qcProduct->account_id;
            $arrivedWeight = $arrivalTicket['arrived_net_weight'];
            $rate = $purchaseOrder->rate_per_kg;
            $totalAmount = $inventoryAmount;

            if ($arrivalTicket->saudaType->name == 'Pohanch') {
                $txn = Transaction::where('voucher_no', $uniqueNo)
                    ->where('purpose', 'supplier-payable')
                    ->where('against_reference_no', $referenceNo)
                    ->first();

                $supplierData = [
                    'amount' => $supplierNetAmount,
                    'account_id' => $qcAccountId,
                    'type' => 'credit',
                    'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
                ];

                if ($txn) {
                    $txn->update($supplierData);
                } else {
                    createTransaction(
                        $supplierNetAmount,
                        $qcAccountId,
                        1,
                        $uniqueNo,
                        'credit',
                        'no',
                        [
                            'purpose' => "supplier-payable",
                            'payment_against' => "pohanch-purchase",
                            'against_reference_no' => $referenceNo,
                            'remarks' => $supplierData['remarks'],
                        ]
                    );
                }
            }

            $txnInv = Transaction::where('voucher_no', $uniqueNo)
                ->where('purpose', 'arrival-slip')
                ->where('against_reference_no', $referenceNo)
                ->first();

            $invData = [
                'amount' => $inventoryAmount,
                'account_id' => $qcAccountId,
                'type' => 'debit',
                'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $arrivedWeight kg) at rate $rate/kg. Total amount: $totalAmount to be paid to supplier."
            ];

            if ($txnInv) {
                $txnInv->update($invData);
            } else {
                createTransaction(
                    $inventoryAmount,
                    $qcAccountId,
                    1,
                    $uniqueNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "arrival-slip",
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => $referenceNo,
                        'remarks' => $invData['remarks']
                    ]
                );
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
        $arrivalTicket = ArrivalTicket::find($request->ticket_id);
        $query = ArrivalPurchaseOrder::with([
            'supplier',
            'product',
            'totalLoadingWeight',
            'totalArrivedNetWeight',
            'totalClosingTrucksQty'
        ])
            ->where('status', 'draft')
            ->where('supplier_id', $arrivalTicket->accounts_of_id)
            ->where('company_location_id', $arrivalTicket->location_id);

        if ($arrivalTicket?->sauda_type_id) {
            $query->where('sauda_type_id', $arrivalTicket->sauda_type_id);
        }

        $linkedPurchaseOrder = $arrivalTicket->purchaseOrder ?? null;

        if ($linkedPurchaseOrder) {
            $query->where('id', '!=', $linkedPurchaseOrder->id);
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

        $contracts = $query->orderBy('created_at', 'desc')->get()->map(function ($c) {
            $totalClosingTrucks = $c->totalClosingTrucksQty->first()->total_closing_trucks_qty ?? 0;

            return [
                'id' => $c->id,
                'contract_no' => $c->contract_no,
                'qc_product_name' => $c->product->name,
                'supplier' => $c->supplier,
                'total_quantity' => $c->total_quantity,
                'min_quantity' => $c->min_quantity,
                'max_quantity' => $c->max_quantity,
                'remaining_quantity' => $c->remaining_quantity,
                'calculation_type' => $c->calculation_type,
                'arrived_quantity' => $c->arrived_quantity,
                'truck_no' => $c->truck_no,
                'trucks_arrived' => $c->trucks_arrived,
                'no_of_trucks' => $c->no_of_trucks,
                'is_replacement' => $c->is_replacement ? 'Yes' : 'No',
                'remaining_trucks' => $c->no_of_trucks - $totalClosingTrucks,
                'status' => $c->status ?: 'N/A',
                'contract_date_formatted' => $c->created_at->format('d-M-Y'),
                'total_loading_weight' => $c->totalArrivedNetWeight->total_arrived_net_weight ?? null,
                'closed_arrivals' => $totalClosingTrucks,
                'remarks' => $c->remarks ?? 'N/A',
            ];
        })->toArray();

        if ($linkedPurchaseOrder) {
            if (!$linkedPurchaseOrder->relationLoaded('totalClosingTrucksQty')) {
                $linkedPurchaseOrder->load('totalClosingTrucksQty');
            }

            $linkedClosingTrucks = $linkedPurchaseOrder->totalClosingTrucksQty->first()->total_closing_trucks_qty ?? 0;

            $linkedContract = [
                'id' => $linkedPurchaseOrder->id,
                'contract_no' => $linkedPurchaseOrder->contract_no,
                'qc_product_name' => $linkedPurchaseOrder->product->name,
                'supplier' => $linkedPurchaseOrder->supplier,
                'total_quantity' => $linkedPurchaseOrder->total_quantity,
                'min_quantity' => $linkedPurchaseOrder->min_quantity,
                'max_quantity' => $linkedPurchaseOrder->max_quantity,
                'remaining_quantity' => $linkedPurchaseOrder->remaining_quantity,
                'calculation_type' => $linkedPurchaseOrder->calculation_type,
                'arrived_quantity' => $linkedPurchaseOrder->arrived_quantity,
                'truck_no' => $linkedPurchaseOrder->truck_no,
                'trucks_arrived' => $linkedPurchaseOrder->trucks_arrived,
                'no_of_trucks' => $linkedPurchaseOrder->no_of_trucks,
                'is_replacement' => $linkedPurchaseOrder->is_replacement ? 'Yes' : 'No',
                'remaining_trucks' => $linkedPurchaseOrder->no_of_trucks - $linkedClosingTrucks,
                'status' => $linkedPurchaseOrder->status ?: 'N/A',
                'contract_date_formatted' => $linkedPurchaseOrder->created_at->format('d-M-Y'),
                'total_loading_weight' => $linkedPurchaseOrder->totalArrivedNetWeight->total_arrived_net_weight ?? null,
                'closed_arrivals' => $linkedClosingTrucks,
                'remarks' => $linkedPurchaseOrder->remarks ?? 'N/A',
                'is_linked' => true,
            ];

            array_unshift($contracts, $linkedContract);
        }

        $html = view('management.procurement.raw_material.ticket_contracts.contract_table', compact('arrivalTicket', 'contracts'))->render();

        return response()->json(['success' => true, 'html' => $html, 'data' => $contracts]);
    }
}
