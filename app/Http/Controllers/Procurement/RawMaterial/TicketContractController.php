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
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Miller;
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use App\Models\Master\QcReliefParameter;
use App\Models\Procurement\PurchaseFreight;
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
        $commodities = Product::all();
        $millers = Miller::all();
        return view('management.procurement.raw_material.ticket_contracts.index', compact('commodities', 'millers'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $isOnlyVerified = str()->contains($request->route()->getName(), 'verified');
        $tickets = ArrivalTicket::select('arrival_tickets.*', 'grn_numbers.unique_no as grn_unique_no')
            ->leftJoin('arrival_slips', 'arrival_tickets.id', '=', 'arrival_slips.arrival_ticket_id')
            ->leftJoin('grn_numbers', function ($join) {
                $join->on('arrival_slips.id', '=', 'grn_numbers.model_id')
                    ->where('grn_numbers.model_type', 'arrival-slip');
            })
            ->where('is_ticket_verified', '=', $isOnlyVerified ? 1 : 0)
            ->where(function ($query) {
                $query->where('arrival_tickets.freight_status', 'completed')
                    ->orWhere('arrival_tickets.first_qc_status', 'rejected');
            })
            ->when($request->filled('grn_no'), function ($q) use ($request) {
                return $q->where('grn_numbers.unique_no', 'like', '%' . $request->grn_no . '%');
            })
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.truck_no', 'like', '%' . $request->truck_no . '%');
            })
            ->when($request->filled('bilty_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.bilty_no', 'like', '%' . $request->bilty_no . '%');
            })
            ->when($request->filled('arrival_ticket_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.unique_no', 'like', '%' . $request->arrival_ticket_no . '%');
            })
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                return $q->where(function ($subQuery) use ($request) {
                    $subQuery->whereHas('qcProduct', function ($query) use ($request) {
                        $query->where('id', $request->commodity_id);
                    })
                        ->orWhereHas('product', function ($query) use ($request) {
                            $query->where('id', $request->commodity_id);
                        });
                });
            })
            ->when($request->filled('miller_id'), function ($q) use ($request) {
                return $q->whereHas('miller', function ($query) use ($request) {
                    $query->where('id', $request->miller_id);
                });
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                    ->whereDate('arrival_tickets.created_at', '<=', $endDate);
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
        $samplingRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

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
            'closing_trucks_qty' => 'required|numeric|min:0.01',
            'selected_freight' => 'nullable|exists:purchase_freights,id'
        ]);

        try {
            DB::beginTransaction();

            $arrivalTicketbeforeUpdate = ArrivalTicket::findOrFail($request->arrival_ticket_id);
            $arrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);
            $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->selected_contract);
            $amount = $arrivalTicket->arrived_net_weight * $purchaseOrder->rate_per_kg;
            $truckNo = $arrivalTicket->truck_no ?? 'N/A';
            $biltyNo = $arrivalTicket->bilty_no ?? 'N/A';
            //  $grnNo = $arrivalTicket->arrivalSlip->unique_no;
            $freightTruckMatches = null;
            $freightBiltyMatches = null;

            if ($request->selected_freight) {
                PurchaseFreight::where('arrival_ticket_id', $arrivalTicket->id)
                    ->update(['arrival_ticket_id' => null]);

                $selectedFreight = PurchaseFreight::findOrFail($request->selected_freight);

                // $freightTruckMatches = strtolower($selectedFreight->truck_no) === strtolower($truckNo);
                // $freightBiltyMatches = strtolower($selectedFreight->bilty_no) === strtolower($biltyNo);
                $freightTruckMatches = strtolower($selectedFreight->truck_no);
                $freightBiltyMatches = strtolower($selectedFreight->bilty_no);

                $selectedFreight->update(['arrival_ticket_id' => $arrivalTicket->id]);
            }

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


            if ($arrivalTicket->first_qc_status == 'rejected') {
                DB::commit();

                return response()->json([
                    'success' => 'Ticket successfully linked to contract',
                    'redirect' => route('raw-material.ticket-contracts.index')
                ]);
            }

            $grnNo = $arrivalTicket->arrivalSlip->unique_no;
            $referenceNo = "$truckNo/$biltyNo";
            $paymentDetails = calculatePaymentDetails($arrivalTicket->id, 1);
            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();
            $contractNo = $arrivalTicket->purchaseOrder->contract_no ?? 'N/A';
            $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;
            $supplierNetAmount = $paymentDetails['calculations']['supplier_net_amount'] ?? 0;
            $type = $arrivalTicket->saudaType->name == 'Pohanch' ? 'pohanch' : 'thadda';

            $qcAccountId = $type == 'pohanch' ? $arrivalTicket->qcProduct->account_id : $purchaseOrder->qcProduct->account_id;
            $arrivedWeight = $arrivalTicket['arrived_net_weight'];
            $rate = $purchaseOrder->rate_per_kg;
            $totalAmount = $inventoryAmount;
            $loadingWeight = null;
            if ($arrivalTicket->saudaType->name == 'Pohanch') {
                $loadingWeight = $arrivedWeight;
                $txn = Transaction::where('purpose', 'supplier-payable')
                    ->where('grn_no', $grnNo)
                    ->where('against_reference_no', $referenceNo)
                    ->first();

                $supplierData = [
                    'amount' => $supplierNetAmount,
                    'account_id' => $purchaseOrder->supplier->account_id,
                    'type' => 'credit',
                    'counter_account_id' => $qcAccountId,
                    'voucher_no' => $purchaseOrder->contract_no,
                    'grn_no' => $grnNo,
                    'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
                ];

                if ($txn) {
                    $txn->update($supplierData);
                } else {
                    createTransaction(
                        $supplierNetAmount,
                        $purchaseOrder->supplier->account_id,
                        1,
                        $purchaseOrder->contract_no,
                        'credit',
                        'no',
                        [
                            'grn_no' => $grnNo,
                            'counter_account_id' => $qcAccountId,
                            'purpose' => "supplier-payable",
                            'payment_against' => $type . "-purchase",
                            'against_reference_no' => $referenceNo,
                            'remarks' => $supplierData['remarks'],
                        ]
                    );
                }

                $txnInv = Transaction::where('grn_no', $grnNo)
                    ->where('purpose', 'arrival-slip')
                    ->where('against_reference_no', $referenceNo)
                    ->first();

                if ($txnInv) {
                    $txnInv->update([
                        'amount' => $inventoryAmount,
                        'account_id' => $qcAccountId,
                        'counter_account_id' => $purchaseOrder->supplier->account_id,
                        'type' => 'debit',
                        'voucher_no' => $purchaseOrder->contract_no,
                        'grn_no' => $grnNo,
                        'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $loadingWeight kg) at rate $rate/kg. Total amount: $totalAmount to be paid to supplier."
                    ]);
                } else {
                    createTransaction(
                        $inventoryAmount,
                        $qcAccountId,
                        1,
                        $purchaseOrder->contract_no,
                        'debit',
                        'no',
                        [
                            'grn_no' => $grnNo,
                            'counter_account_id' => $purchaseOrder->supplier->account_id,
                            'purpose' => "arrival-slip",
                            'payment_against' => $type . "-purchase",
                            'against_reference_no' => $referenceNo,
                            'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $loadingWeight kg) at rate $rate/kg. Total amount: $totalAmount to be paid to supplier."
                        ]
                    );
                }
            }

            if ($arrivalTicket->saudaType->name == 'Thadda') {
                $purchaseFreight = PurchaseFreight::whereRaw('LOWER(truck_no) = ?', [strtolower($freightTruckMatches)])
                    ->whereRaw('LOWER(bilty_no) = ?', [strtolower($freightBiltyMatches)])
                    ->first();

                if ($freightBiltyMatches && $freightTruckMatches && $purchaseFreight && isset($purchaseFreight->purchaseTicket)) {
                    $purchaseTicket = $purchaseFreight->purchaseTicket;
                    $loadingWeight = $purchaseFreight->loading_weight ?? 0;
                    $purchasePaymentDetail = calculatePaymentDetails($purchaseTicket->id, 2);
                    $amount = $purchasePaymentDetail['calculations']['supplier_net_amount'] ?? 0;
                    $inventoryAmount = $purchasePaymentDetail['calculations']['inventory_amount'] ?? 0;
                    $productName = $purchaseOrder->qcProduct->name ?? $purchaseOrder->product->name;
                    $qcAccountId = $purchaseOrder->qcProduct->account_id;
                    $truckNo = $freightTruckMatches;
                    $biltyNo = $freightBiltyMatches;

                    $stockTrx = Transaction::where('grn_no', $grnNo)
                        //where('voucher_no', $contractNo)
                        ->where('purpose', 'stock-in-transit')
                        ->where('type', 'credit')
                        //  ->where('against_reference_no', "$freightTruckMatches/$freightBiltyMatches")
                        ->first();

                    if ($stockTrx) {
                        $stockTrx->update([
                            'amount' => $inventoryAmount,
                            'account_id' => $stockInTransitAccount->id,
                            'counter_account_id' => $qcAccountId,
                            'voucher_no' => $purchaseOrder->contract_no,
                            'grn_no' => $grnNo,
                            'remarks' => "Stock-in-transit recorded for arrival of " . $productName . " under contract ($contractNo) via Bilty: $freightBiltyMatches - Truck No: $freightTruckMatches. Weight: {$loadingWeight} kg at rate {$purchaseTicket->purchaseOrder->rate_per_kg}/kg."
                        ]);
                    } else {
                        createTransaction(
                            $inventoryAmount,
                            $stockInTransitAccount->id,
                            1,
                            $contractNo,
                            'credit',
                            'no',
                            [
                                'purpose' => "stock-in-transit",
                                'counter_account_id' => $qcAccountId,
                                'payment_against' => "thadda-purchase",
                                'against_reference_no' => "$freightTruckMatches/$freightBiltyMatches",
                                'grn_no' => $grnNo,
                                'remarks' => "Stock-in-transit recorded for arrival of " . $productName . " under contract ($contractNo) via Bilty: $freightBiltyMatches - Truck No: $freightTruckMatches. Weight: {$loadingWeight} kg at rate {$purchaseTicket->purchaseOrder->rate_per_kg}/kg."
                            ]
                        );
                    }

                    $txnInv = Transaction::where('grn_no', $grnNo)
                        //where('voucher_no', $contractNo)
                        ->where('purpose', 'arrival-slip')
                        ->where('type', 'debit')
                        //->where('against_reference_no', $referenceNo)
                        ->first();

                    if ($txnInv) {
                        $txnInv->update([
                            'amount' => $inventoryAmount,
                            'account_id' => $qcAccountId,
                            'counter_account_id' => $purchaseOrder->supplier->account_id,
                            'grn_no' => $grnNo,
                            'voucher_no' => $purchaseOrder->contract_no,
                            'type' => 'debit',
                            'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $loadingWeight kg) at rate $rate/kg. Total amount: $totalAmount to be paid to supplier."
                        ]);
                    } else {
                        createTransaction(
                            $inventoryAmount,
                            $qcAccountId,
                            1,
                            $contractNo,
                            'debit',
                            'no',
                            [
                                'purpose' => "arrival-slip",
                                'counter_account_id' => $purchaseOrder->supplier->account_id,
                                'grn_no' => $grnNo,
                                'payment_against' => $type . "-purchase",
                                'against_reference_no' => $referenceNo,
                                'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $loadingWeight kg) at rate $rate/kg. Total amount: $totalAmount to be paid to supplier."
                            ]
                        );
                    }

                    $supplierTxn = Transaction::where('grn_no', $grnNo)
                        //where('voucher_no', $contractNo)
                        ->where('purpose', 'supplier-payable')
                        // ->where('against_reference_no', "$freightTruckMatches/$freightBiltyMatches")
                        ->first();

                    if ($supplierTxn) {
                        $supplierTxn->update([
                            'amount' => $purchasePaymentDetail['calculations']['supplier_net_amount'] ?? 0,
                            'account_id' => $purchaseOrder->supplier->account_id,
                            'counter_account_id' => $qcAccountId,
                            'voucher_no' => $purchaseOrder->contract_no,
                            'grn_no' => $grnNo,
                            'type' => 'credit',
                            'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $freightBiltyMatches - Truck No: $freightTruckMatches. Amount payable to the supplier.",
                        ]);
                    } else {
                        createTransaction(
                            $purchasePaymentDetail['calculations']['supplier_net_amount'] ?? 0,
                            $purchaseOrder->supplier->account_id,
                            1,
                            $contractNo,
                            'credit',
                            'no',
                            [
                                'purpose' => "supplier-payable",
                                'counter_account_id' => $qcAccountId,
                                'grn_no' => $grnNo,
                                'payment_against' => "thadda-purchase",
                                'against_reference_no' => "$freightTruckMatches/$freightBiltyMatches",
                                'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $freightBiltyMatches - Truck No: $freightTruckMatches. Amount payable to the supplier.",
                            ]
                        );
                    }
                }
            }




            $BrokerLedgerDelete = Transaction::where('grn_no', $grnNo)
                ->where('purpose', 'broker')
                ->where('payment_against', $type . '-purchase')
                ->delete();



            if ($arrivalTicket->purchaseOrder->broker_one_id && $arrivalTicket->purchaseOrder->broker_one_commission && $loadingWeight) {
                $amount = ($loadingWeight * $arrivalTicket->purchaseOrder->broker_one_commission);

                createTransaction(
                    $amount,
                    $arrivalTicket->purchaseOrder->broker->account_id,
                    1,
                    $contractNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => $type . "-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "' . $type . '" purchase. Amount to be paid to broker.'
                    ]
                );
            }


            if ($arrivalTicket->purchaseOrder->broker_two_id && $arrivalTicket->purchaseOrder->broker_two_commission && $loadingWeight) {
                $amount = ($loadingWeight * $arrivalTicket->purchaseOrder->broker_two_commission);

                createTransaction(
                    $amount,
                    $arrivalTicket->purchaseOrder->brokerTwo->account_id,
                    1,
                    $contractNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => $type . "-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "' . $type . '" purchase. Amount to be paid to broker.'
                    ]
                );

            }

            if ($arrivalTicket->purchaseOrder->broker_three_id && $arrivalTicket->purchaseOrder->broker_three_commission && $loadingWeight) {
                $amount = ($loadingWeight * $arrivalTicket->purchaseOrder->broker_three_commission);

                createTransaction(
                    $amount,
                    $arrivalTicket->purchaseOrder->brokerThree->account_id,
                    1,
                    $contractNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => $type . "-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "' . $type . '" purchase. Amount to be paid to broker.'
                    ]
                );

            }

            DB::commit();

            return response()->json([
                'success' => 'Ticket successfully linked to contract',
                'redirect' => route('raw-material.ticket-contracts.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to link ticket to contract',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
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
        $samplingRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

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
        $ticketId = $request->ticket_id;

        $query = ArrivalPurchaseOrder::with([
            'supplier',
            'product',
            'totalLoadingWeight',
            'totalArrivedNetWeight',
            'totalClosingTrucksQty',
            'totalClosingTrucksQtyWithoutOwnTicket' => function ($query) use ($request) {
                $query->where('id', '!=', $request->ticket_id);
            },
            'saudaType',
            'createdByUser',
            'rejectedArrivalTickets',
            'stockInTransitTickets'
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

        $contracts = $query->orderBy('created_at', 'desc')->get()->map(function ($c) use ($arrivalTicket) {

            //$totalClosingTrucks = $c->totalClosingTrucksQty->first()->total_closing_trucks_qty ?? 0;
            $totalClosingTrucks = $c->approvedArrivalTickets()->sum('closing_trucks_qty') ?? 0;
            $totalClosingTrucksWithoutTicket = $c->totalClosingTrucksQtyWithoutOwnTicket->first()->total_closing_trucks_qty ?? 0;
            $rejectedTrucks = $c->rejectedArrivalTickets->count();
            $stockInTransitTrucks = $c->stockInTransitTickets->count();

            $purchaseFreights = PurchaseFreight::where('arrival_purchase_order_id', $c->id)
                ->where(function ($query) use ($arrivalTicket) {
                    $query->whereNull('arrival_ticket_id')
                        ->orWhere('arrival_ticket_id', $arrivalTicket->id);
                })
                ->get()
                ->map(function ($freight) use ($arrivalTicket) {
                    $isSelected = false;

                    if ($freight->arrival_ticket_id == $arrivalTicket->id) {
                        $isSelected = true;
                    } elseif (
                        !$isSelected && !$freight->arrival_ticket_id &&
                        strtolower($freight->truck_no) === strtolower($arrivalTicket->truck_no) &&
                        strtolower($freight->bilty_no) === strtolower($arrivalTicket->bilty_no)
                    ) {
                        $isSelected = true;
                    }

                    return [
                        'id' => $freight->id,
                        'truck_no' => $freight->truck_no,
                        'bilty_no' => $freight->bilty_no,
                        'loading_weight' => $freight->loading_weight,
                        'loading_date' => $freight->loading_date?->format('d-M-Y'),
                        'station_name' => $freight->station_name,
                        'no_of_bags' => $freight->no_of_bags,
                        'is_selected' => $isSelected,
                        'arrival_ticket_id' => $freight->arrival_ticket_id,
                    ];
                });

            return [
                'id' => $c->id,
                'contract_no' => $c->contract_no,
                'qc_product_name' => $c->product->name,
                'supplier' => $c->supplier,
                'broker_one_name' => $c->broker_one_name,
                'broker_two_name' => $c->broker_two_name,
                'broker_three_name' => $c->broker_three_name,
                'created_by_user' => $c->createdByUser,
                'rate_per_kg' => $c->rate_per_kg,
                'rate_per_mound' => $c->rate_per_mound,
                'rate_per_100kg' => $c->rate_per_100kg,
                'delivery_date' => $c->delivery_date,
                'sauda_type' => $c->saudaType,
                'is_replacement' => $c->is_replacement ? 'Yes' : 'No',
                'remarks' => $c->remarks,
                'no_of_trucks' => $c->no_of_trucks,
                'min_quantity' => $c->min_quantity,
                'max_quantity' => $c->max_quantity,
                'total_quantity' => $c->total_quantity,
                'remaining_quantity' => $c->remaining_quantity,
                'calculation_type' => $c->calculation_type,
                'arrived_quantity' => $c->arrived_quantity,
                'truck_no' => $c->truck_no,
                'trucks_arrived' => $c->trucks_arrived,
                'status' => $c->status,
                'contract_date_formatted' => $c->created_at->format('d-M-Y'),
                'total_loading_weight' => $c->totalArrivedNetWeight->total_arrived_net_weight ?? 0,
                'closed_arrivals' => $totalClosingTrucks,
                'closed_arrivals_without_own' => $totalClosingTrucksWithoutTicket,
                'rejected_trucks' => $rejectedTrucks,
                'stock_in_transit_trucks' => $stockInTransitTrucks,
                'purchase_type' => $c->purchase_type,
                'purchase_freights' => $purchaseFreights,
            ];
        })->toArray();

        if ($linkedPurchaseOrder) {
            if (!$linkedPurchaseOrder->relationLoaded('totalClosingTrucksQty')) {
                $linkedPurchaseOrder->load([
                    'totalClosingTrucksQty',
                    'rejectedArrivalTickets',
                    'stockInTransitTickets'
                ]);
            }
            if (!$linkedPurchaseOrder->relationLoaded('totalClosingTrucksQtyWithoutOwnTicket')) {
                $linkedPurchaseOrder->load([
                    'totalClosingTrucksQtyWithoutOwnTicket' => function ($query) use ($ticketId) {
                        $query->where('id', '!=', $ticketId);
                    },
                    'rejectedArrivalTickets',
                    'stockInTransitTickets'
                ]);
            }

            // $linkedClosingTrucks = $linkedPurchaseOrder->totalClosingTrucksQty->first()->total_closing_trucks_qty ?? 0;
            $linkedClosingTrucks = $linkedPurchaseOrder->approvedArrivalTickets()->sum('closing_trucks_qty') ?? 0;
            $linkedClosingTrucksWithoutOwn = $linkedPurchaseOrder->totalClosingTrucksQtyWithoutOwnTicket->first()->total_closing_trucks_qty ?? 0;
            $linkedRejectedTrucks = $linkedPurchaseOrder->rejectedArrivalTickets->count();
            $linkedStockInTransitTrucks = $linkedPurchaseOrder->stockInTransitTickets->count();

            $linkedPurchaseFreights = PurchaseFreight::where('arrival_purchase_order_id', $linkedPurchaseOrder->id)
                ->where(function ($query) use ($arrivalTicket) {
                    $query->whereNull('arrival_ticket_id')
                        ->orWhere('arrival_ticket_id', $arrivalTicket->id);
                })
                ->get();

            $hasExactTicketMatch = $linkedPurchaseFreights->contains(function ($freight) use ($arrivalTicket) {
                return $freight->arrival_ticket_id == $arrivalTicket->id;
            });

            $linkedPurchaseFreights = $linkedPurchaseFreights->map(function ($freight) use ($arrivalTicket, $hasExactTicketMatch) {
                $isSelected = false;

                if ($freight->arrival_ticket_id == $arrivalTicket->id) {
                    $isSelected = true;
                } elseif (
                    !$hasExactTicketMatch &&
                    !$freight->arrival_ticket_id &&
                    strtolower($freight->truck_no) === strtolower($arrivalTicket->truck_no) &&
                    strtolower($freight->bilty_no) === strtolower($arrivalTicket->bilty_no)
                ) {
                    $isSelected = true;
                }

                return [
                    'id' => $freight->id,
                    'truck_no' => $freight->truck_no,
                    'bilty_no' => $freight->bilty_no,
                    'loading_weight' => $freight->loading_weight,
                    'loading_date' => $freight->loading_date?->format('d-M-Y'),
                    'station_name' => $freight->station_name,
                    'no_of_bags' => $freight->no_of_bags,
                    'is_selected' => $isSelected,
                    'arrival_ticket_id' => $freight->arrival_ticket_id,
                ];
            });


            $linkedContract = [
                'id' => $linkedPurchaseOrder->id,
                'contract_no' => $linkedPurchaseOrder->contract_no,
                'qc_product_name' => $linkedPurchaseOrder->product->name,
                'supplier' => $linkedPurchaseOrder->supplier,
                'broker_one_name' => $linkedPurchaseOrder->broker_one_name,
                'broker_two_name' => $linkedPurchaseOrder->broker_two_name,
                'broker_three_name' => $linkedPurchaseOrder->broker_three_name,
                'created_by_user' => $linkedPurchaseOrder->createdByUser,
                'rate_per_kg' => $linkedPurchaseOrder->rate_per_kg,
                'rate_per_mound' => $linkedPurchaseOrder->rate_per_mound,
                'rate_per_100kg' => $linkedPurchaseOrder->rate_per_100kg,
                'delivery_date' => $linkedPurchaseOrder->delivery_date,
                'sauda_type' => $linkedPurchaseOrder->saudaType,
                'is_replacement' => $linkedPurchaseOrder->is_replacement ? 'Yes' : 'No',
                'remarks' => $linkedPurchaseOrder->remarks,
                'no_of_trucks' => $linkedPurchaseOrder->no_of_trucks,
                'min_quantity' => $linkedPurchaseOrder->min_quantity,
                'max_quantity' => $linkedPurchaseOrder->max_quantity,
                'total_quantity' => $linkedPurchaseOrder->total_quantity,
                'remaining_quantity' => $linkedPurchaseOrder->remaining_quantity,
                'calculation_type' => $linkedPurchaseOrder->calculation_type,
                'arrived_quantity' => $linkedPurchaseOrder->arrived_quantity,
                'truck_no' => $linkedPurchaseOrder->truck_no,
                'trucks_arrived' => $linkedPurchaseOrder->trucks_arrived,
                'status' => $linkedPurchaseOrder->status,
                'contract_date_formatted' => $linkedPurchaseOrder->created_at->format('d-M-Y'),
                'total_loading_weight' => $linkedPurchaseOrder->totalArrivedNetWeight->total_arrived_net_weight ?? 0,
                'closed_arrivals' => $linkedClosingTrucks,
                'closed_arrivals_without_own' => $linkedClosingTrucksWithoutOwn,
                'rejected_trucks' => $linkedRejectedTrucks,
                'stock_in_transit_trucks' => $linkedStockInTransitTrucks,
                'purchase_type' => $linkedPurchaseOrder->purchase_type,
                'is_linked' => true,
                'purchase_freights' => $linkedPurchaseFreights,
            ];

            array_unshift($contracts, $linkedContract);
        }
        dd($contracts);

        $html = view('management.procurement.raw_material.ticket_contracts.contract_table', compact('arrivalTicket', 'contracts'))->render();

        return response()->json(['success' => true, 'html' => $html, 'data' => $contracts]);
    }
}
