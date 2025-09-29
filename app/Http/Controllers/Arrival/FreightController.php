<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\FreightRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSlip;
use App\Models\Arrival\Freight;
use App\Models\Master\Account\Account;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\GrnNumber;
use App\Models\Procurement\PurchaseFreight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FreightController extends Controller
{
    public function index()
    {
        return view('management.arrival.freight.index');
    }

    public function getList(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $query = Freight::with(['arrivalTicket'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('ticket_number', 'like', $searchTerm)
                        ->orWhere('truck_number', 'like', $searchTerm)
                        ->orWhere('billy_number', 'like', $searchTerm);
                });
            })
            ->where('company_id', $request->company_id)
            ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                return $q->whereHas('arrivalTicket', function ($query) use ($authUser) {
                    $query->where('location_id', $authUser->company_location_id);
                });
            });

        $freights = $query->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.arrival.freight.getList', compact('freights'));
    }

    public function create()
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $tickets = ArrivalTicket::where('freight_status', 'pending')
            ->whereNotNull('qc_product')
            // where('arrival_purchase_order_id', '!=', Null)
            ->when(!$isSuperAdmin, function ($query) use ($authUser) {
                // return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                return $query->where('location_id', $authUser->company_location_id);
                // });
            })
            ->get();

        return view('management.arrival.freight.create', ['tickets' => $tickets]);
    }

    public function store(FreightRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->all();

                $ticket = ArrivalTicket::where('id', $request->arrival_ticket_id)->first();

                if ($ticket->freight_status !== 'pending') {
                    return response('Freight has already been completed and cannot be performed again.', 422);
                }


                if ($ticket) {
                    $ticket->update([
                        'freight_status' => 'completed',
                        'arrival_slip_status' => 'generated'
                    ]);
                }

                $data['arrived_weight'] = $request->arrived_weight ?? 0;
                $data['loaded_weight'] = $request->loaded_weight ?? 0;
                $data['company_id'] = $request->company_id;
                $data['exempted_weight'] = $request->exempted_weight ?? 0;

                $freight = Freight::create($data);

                $datePrefix = date('m-d-Y') . '-';
                // $data['unique_no'] = generateUniqueNumberByDate('arrival_slips', $datePrefix, null, 'unique_no');
                $data['unique_no'] = $grnNo = generateLocationBasedCode('grn_numbers', $ticket->location?->code ?? 'KHI');
                $data['creator_id'] = auth()->user()->id;
                $data['remark'] = $request->note ?? '';

                $arrivalApprove = ArrivalSlip::create($data);

                $grnNumber = GrnNumber::create([
                    'model_id' => $arrivalApprove->id,
                    'model_type' => 'arrival-slip',
                    'location_id' => $ticket->location_id,
                    'product_id' => $ticket->qc_product ?? $ticket->product_id ?? null,
                    'unique_no' => $grnNo
                ]);

                $truckNo = $ticket->truck_no ?? 'N/A';
                $biltyNo = $ticket->bilty_no ?? 'N/A';
                $purchaseOrder = $ticket->purchaseOrder ?? 'N/A';

                if ($ticket->arrival_purchase_order_id) {
                    $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();

                    // $amount = $data['arrived_weight'] * $ticket->purchaseOrder->rate_per_kg;
                    $paymentDetails = calculatePaymentDetails($ticket->id, 1);

                    $amount = $paymentDetails['calculations']['supplier_net_amount'] ?? 0;
                    $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;

                    $contractNo = $ticket->purchaseOrder->contract_no ?? 'N/A';
                    $qcProduct = $ticket->purchaseOrder->qcProduct->name ?? $ticket->purchaseOrder->product->name ?? 'N/A';
                    $loadingWeight = $ticket->arrived_net_weight;

                    if ($ticket->saudaType->name == 'Pohanch') {
                        createTransaction(
                            $amount,
                            $ticket->accountsOf->account_id,
                            1,
                            $contractNo,
                            'credit',
                            'no',
                            [
                                'grn_no' => $grnNo,
                                'counter_account_id' => $ticket->qcProduct->account_id,
                                'purpose' => "supplier-payable",
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
                            ]
                        );

                        if ($ticket->purchaseOrder->broker_one_id && $ticket->purchaseOrder->broker_one_commission && $loadingWeight) {
                            $amount = ($loadingWeight * $ticket->purchaseOrder->broker_one_commission);

                            createTransaction(
                                $amount,
                                $ticket->purchaseOrder->broker->account_id,
                                1,
                                $ticket->purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'grn_no' => $grnNo,
                                    'purpose' => "broker",
                                    'counter_account_id' => $ticket->qcProduct->account_id,
                                    'payment_against' => "pohanch-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }

                        if ($ticket->purchaseOrder->broker_two_id && $ticket->purchaseOrder->broker_two_commission && $loadingWeight) {
                            $amount = ($loadingWeight * $ticket->purchaseOrder->broker_two_commission);

                            createTransaction(
                                $amount,
                                $ticket->purchaseOrder->brokerTwo->account_id,
                                1,
                                $ticket->purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'grn_no' => $grnNo,
                                    'purpose' => "broker",
                                    'counter_account_id' => $ticket->qcProduct->account_id,
                                    'payment_against' => "pohanch-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }

                        if ($ticket->purchaseOrder->broker_three_id && $ticket->purchaseOrder->broker_three_commission && $loadingWeight) {
                            $amount = ($loadingWeight * $ticket->purchaseOrder->broker_three_commission);

                            createTransaction(
                                $amount,
                                $ticket->purchaseOrder->brokerThree->account_id,
                                1,
                                $ticket->purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'grn_no' => $grnNo,
                                    'purpose' => "broker",
                                    'counter_account_id' => $ticket->qcProduct->account_id,
                                    'payment_against' => "pohanch-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }

                        createTransaction(
                            $inventoryAmount,
                            $ticket->qcProduct->account_id,
                            1,
                            $contractNo,
                            'debit',
                            'no',
                            [
                                'grn_no' => $grnNo,
                                'counter_account_id' => $ticket->purchaseOrder->supplier->account_id,
                                'purpose' => "arrival-slip",
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $data['arrived_weight'] . ' kg) at rate ' . $ticket->purchaseOrder->rate_per_kg . '/kg.'
                            ]
                        );
                    } else {
                        $purchaseFreight = PurchaseFreight::whereRaw('LOWER(truck_no) = ?', [strtolower($truckNo)])
                            ->whereRaw('LOWER(bilty_no) = ?', [strtolower($biltyNo)])
                            ->first();

                        if ($purchaseFreight && isset($purchaseFreight->purchaseTicket)) {
                            $loadingWeight = $purchaseFreight->loading_weight;
                            $purchaseTicket = $purchaseFreight->purchaseTicket;

                            $purchaseFreight->update(['arrival_ticket_id' => $request->arrival_ticket_id]);
                            $purchasePaymentDetail = calculatePaymentDetails($purchaseTicket->id, 2);

                            $amount = $purchasePaymentDetail['calculations']['supplier_net_amount'] ?? 0;
                            $inventoryAmount = $purchasePaymentDetail['calculations']['inventory_amount'] ?? 0;

                            createTransaction(
                                $inventoryAmount,
                                $stockInTransitAccount->id,
                                1,
                                $contractNo,
                                'credit',
                                'no',
                                [
                                    'counter_account_id' => $purchaseOrder->qcProduct->account_id,
                                    'grn_no' => $grnNo,
                                    'purpose' => "stock-in-transit",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => "Stock-in-transit recorded for arrival of $qcProduct under contract ($contractNo) via Bilty: $biltyNo - Truck No: $truckNo. Weight: {$loadingWeight} kg at rate {$purchaseTicket->purchaseOrder->rate_per_kg}/kg."
                                ]
                            );

                            createTransaction(
                                $inventoryAmount,
                                $purchaseOrder->qcProduct->account_id,
                                1,
                                $contractNo,
                                'debit',
                                'no',
                                [
                                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                                    'grn_no' => $grnNo,
                                    'purpose' => "arrival-slip",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseTicket->purchaseOrder->rate_per_kg . '/kg.'
                                ]
                            );
                        }
                    }
                }

                return response()->json(['success' => 'Freight created successfully.', 'data' => ['freight' => $freight, 'slip' => $arrivalApprove]], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $freight = Freight::findOrFail($id);
        return view('management.arrival.freight.edit', compact('freight'));
    }

    public function update(FreightRequest $request, Freight $freight)
    {
        $data = $request->validated();

        $data['difference'] = $data['loaded_weight'] - $data['arrived_weight'];
        $data['net_freight'] = $data['freight_per_ton'] * ($data['loaded_weight'] / 1000);

        $freight->update($data);

        return response()->json(['success' => 'Freight updated successfully.', 'data' => $freight], 200);
    }

    public function destroy(Freight $freight)
    {
        $freight->delete();
        return response()->json(['success' => 'Freight deleted successfully.'], 200);
    }

    public function getFreightForm(Request $request)
    {
        $ticket = ArrivalTicket::with('product')->find($request->arrival_ticket_id);

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        $isNotGeneratable = false;

        $isNotGeneratable = $ticket->decision_making == 1;

        $html = view('management.arrival.freight.partials.freight_form', [
            'ticket' => $ticket,
            'isNotGeneratable' => $isNotGeneratable,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
}
