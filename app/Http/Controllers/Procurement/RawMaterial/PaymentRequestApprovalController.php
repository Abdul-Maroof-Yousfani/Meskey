<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PaymentRequestApprovalRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Vendor;
use App\Models\Procurement\FreightPaymentRequest;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentRequestApprovalController extends Controller
{
    public function index()
    {
        return view('management.procurement.raw_material.payment_request_approval.index');
    }

    public function getList(Request $request)
    {
        $paymentRequests = PaymentRequest::with([
            'paymentRequestData.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
            'paymentRequestData.arrivalTicket.freight',
            'approvals.approver'
        ])
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
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
            ->where('payment_type', null)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $paymentRequests->getCollection()->transform(function ($request) {
            $freightData = null;

            if ($request->module_type === 'purchase_order') {
                $freightData = optional($request->paymentRequestData)->purchaseTicket->purchaseFreight ?? null;
            } else {
                $freightData = optional($request->paymentRequestData)->arrivalTicket->freight ?? null;
            }

            $request->freight_data = $freightData;

            return $request;
        });

        return view('management.procurement.raw_material.payment_request_approval.getList', [
            'paymentRequests' => $paymentRequests
        ]);
    }

    public function store(PaymentRequestApprovalRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);
            $paymentRequestData = $paymentRequest->paymentRequestData;
            // dd($paymentRequest, $paymentRequestData);
            $moduleType = $paymentRequestData->module_type;
            $ticket = ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? $paymentRequestData->arrivalTicket : $paymentRequestData->purchaseTicket;
            $purchaseOrder = $ticket->purchaseOrder;
            $saudaType = ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? 'pohanch' : 'thadda';
            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();

            $truckNo = $paymentRequestData->arrivalTicket->truck_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
            $biltyNo = $paymentRequestData->arrivalTicket->bilty_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';

            if ($request->has('total_amount') || $request->has('bag_weight')) {
                $this->updatePaymentRequestData($paymentRequestData, $request, $ticket, ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? false : true);
            }

            if (($request->has('sampling_results') || $request->has('compulsory_results') || $request->has('other_deduction'))) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            if ($request->has('payment_request_amount')) {
                $paymentRequest->update(['amount' => $request->payment_request_amount]);
            }

            if ($request->has('freight_pay_request_amount') && $request->freight_pay_request_amount > 0 && $moduleType !== 'ticket') {
                $freightRequest = PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                    ->where('request_type', 'freight_payment')
                    ->first();

                if ($freightRequest) {
                    $freightRequest->update(['amount' => $request->freight_pay_request_amount]);
                } else {
                    PaymentRequest::create([
                        'payment_request_data_id' => $paymentRequestData->id,
                        'request_type' => 'freight_payment',
                        'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                        'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                        'amount' => $request->freight_pay_request_amount
                    ]);
                }
            }

            if ($request->has('bag_weight')) {
                $dataToUpdate = ['bag_weight' => $request->bag_weight];

                if ($moduleType !== 'ticket') {
                    $dataToUpdate = ['bag_weight' => (float) $request->bag_weight];
                }

                $ticket->update($dataToUpdate);
            }

            if ($request->has('other_deduction')) {
                $paymentRequest->update([
                    'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                    'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                ]);
            }

            PaymentRequestApproval::create([
                'payment_request_id' => $request->payment_request_id,
                'payment_request_data_id' => $paymentRequest->payment_request_data_id,
                'ticket_id' => $paymentRequestData->ticket_id,
                'purchase_order_id' => $purchaseOrder->id,
                'approver_id' => auth()->user()->id,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'amount' => $paymentRequest->amount,
                'request_type' => $paymentRequest->request_type
            ]);

            $paymentRequest->update(['status' => $request->status]);

            // ---------------------- 
            if ($moduleType !== 'freight_payment') {
                $paymentDetails = calculatePaymentDetails($ticket->id, $moduleType === 'ticket' ? 1 : 2);
                $grnNo = $ticket->arrivalSlip->unique_no ?? null;
                $contractNo = $purchaseOrder->contract_no;
                $qcProduct = $purchaseOrder->qcProduct->name ?? $purchaseOrder->product->name;
                $loadingWeight = ($moduleType === 'ticket' ? $paymentRequestData->arrivalTicket->arrived_net_weight : $paymentRequestData->purchaseTicket->purchaseFreight->loading_weight) ?? 0;
                $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;
                $accId = $moduleType === 'ticket' ? $ticket->qcProduct->account_id : $stockInTransitAccount->id;

                $amount = $paymentDetails['calculations']['net_amount'] ?? 0;

                $supplierTxn = Transaction::where('voucher_no', $contractNo)
                    ->where('purpose', 'supplier-payable')
                    ->where('against_reference_no', "$truckNo/$biltyNo")
                    ->first();

                $supplierData = [
                    'amount' =>   $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
                    'account_id' => $purchaseOrder->supplier->account_id,
                    'payment_against' => $moduleType === 'ticket' ? "pohanch-purchase" : "thadda-purchase",
                    'grn_no' => $grnNo,
                    'type' => 'credit',
                    'counter_account_id' => $accId,
                    'remarks' => "Accounts payable recorded against the contract ($purchaseOrder->contract_no) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
                ];

                if ($supplierTxn) {
                    $supplierTxn->update($supplierData);
                } else {
                    createTransaction(
                        $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
                        $purchaseOrder->supplier->account_id,
                        1,
                        $contractNo,
                        'credit',
                        'no',
                        [
                            'counter_account_id' => $accId,
                            'grn_no' => $grnNo,
                            'purpose' => "supplier-payable",
                            'payment_against' => $moduleType === 'ticket' ? "pohanch-purchase" : "thadda-purchase",
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => $supplierData['remarks']
                        ]
                    );
                }

                if ($moduleType !== 'ticket') {
                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'stock-in-transit')
                        ->where('type', 'debit')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    $transitTxnCdt = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'stock-in-transit')
                        ->where('type', 'credit')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    $transitData = [
                        'amount' => $inventoryAmount,
                        'counter_account_id' => $purchaseOrder->supplier->account_id,
                        'account_id' => $stockInTransitAccount->id,
                        'remarks' => "Stock-in-transit recorded for arrival of $qcProduct under contract ($contractNo) via Bilty: $biltyNo - Truck No: $truckNo. Weight: {$loadingWeight} kg at rate {$purchaseOrder->rate_per_kg}/kg."
                    ];

                    if ($transitTxnCdt) {
                        $transitTxnCdt->update($transitData);
                    }

                    if ($transitTxn) {
                        $transitTxn->update($transitData);
                    } else {
                        createTransaction(
                            $inventoryAmount,
                            $stockInTransitAccount->id,
                            1,
                            $contractNo,
                            'debit',
                            'no',
                            [
                                'purpose' => "stock-in-transit",
                                'counter_account_id' => $purchaseOrder->supplier->account_id,
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => $transitData['remarks']
                            ]
                        );
                    }

                    $existingFreightTrx = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'thadda-freight')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();
                    $advanceFreight = (int)($request->advance_freight);

                    if ($advanceFreight > 0) {
                        if ($existingFreightTrx) {
                            $existingFreightTrx->update([
                                'amount' => $advanceFreight,
                                'account_id' => $purchaseOrder->supplier->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
                                'type' => 'credit',
                                'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account.",
                            ]);
                        } else {
                            createTransaction(
                                $advanceFreight,
                                $purchaseOrder->supplier->account_id,
                                1,
                                $contractNo,
                                'credit',
                                'no',
                                [
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'purpose' => "thadda-freight",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
                        $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);

                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against',   'thadda-purchase')
                            ->where('account_id', $purchaseOrder->broker->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->broker->account_id,
                                'type' => 'credit',
                                'counter_account_id' => $stockInTransitAccount->id,
                            ]);
                        } else {
                            createTransaction(
                                $amount,
                                $purchaseOrder->broker->account_id,
                                1,
                                $purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'purpose' => "broker",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
                        $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);


                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against',   'thadda-purchase')
                            ->where('account_id', $purchaseOrder->brokerTwo->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->brokerTwo->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
                                'type' => 'credit',
                            ]);
                        } else {
                            createTransaction(
                                $amount,
                                $purchaseOrder->brokerTwo->account_id,
                                1,
                                $purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'purpose' => "broker",
                                    'payment_against' => "thadda-purchase",
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
                        $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);

                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against',   'thadda-purchase')
                            ->where('account_id', $purchaseOrder->brokerThree->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->brokerThree->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
                                'type' => 'credit',
                            ]);
                        } else {
                            createTransaction(
                                $amount,
                                $purchaseOrder->brokerThree->account_id,
                                1,
                                $purchaseOrder->contract_no,
                                'credit',
                                'no',
                                [
                                    'purpose' => "broker",
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }
                    }

                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'arrival-slip')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    if ($transitTxn) {
                        $transitTxn->update([
                            'amount' => $inventoryAmount,
                            'account_id' => $ticket->purchaseOrder->qcProduct->account_id ?? $ticket->purchaseOrder->product->account_id,
                            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                        ]);
                    }
                } else {
                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'arrival-slip')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    if ($transitTxn) {
                        $transitTxn->update([
                            'amount' => $inventoryAmount,
                            // 'account_id' =>    $ticket->purchaseOrder->qcProduct->account_id,
                            'account_id' => $ticket->qcProduct->account_id ?? $ticket->product->account_id,
                            'grn_no' => $grnNo,
                            'type' => 'debit',
                            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                        ]);
                    } else {
                        createTransaction(
                            $amount,
                            $ticket->qcProduct->account_id ?? $ticket->product->account_id,
                            1,
                            $contractNo,
                            'debit',
                            'no',
                            [
                                'purpose' => "arrival-slip",
                                'grn_no' => $grnNo,
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                            ]
                        );
                    }
                }

                if (
                    isset($request->brokery_amount, $request->broker_id) &&
                    $request->brokery_amount < 0
                ) {
                    $existingTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'supplier-brokery')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->exists();

                    if (!$existingTxn) {
                        $broker = Broker::find($request->broker_id);
                        if ($broker && $broker->account_id) {
                            $brokeryAmount = abs($request->brokery_amount);

                            createTransaction(
                                $brokeryAmount,
                                $purchaseOrder->supplier->account_id,
                                1,
                                $contractNo,
                                'debit',
                                'no',
                                [
                                    'purpose' => "supplier-brokery",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => "Brokery amount adjustment against contract ($contractNo). Transferred from supplier to broker."
                                ]
                            );

                            createTransaction(
                                $brokeryAmount,
                                $broker->account_id,
                                1,
                                $contractNo,
                                'credit',
                                'no',
                                [
                                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                                    'purpose' => "supplier-brokery",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => "Brokery amount adjustment received from supplier for contract ($contractNo)."
                                ]
                            );
                        }
                    }
                }
            }
            // ----------------------

            return response()->json([
                'success' => 'Payment request ' . $request->status . ' successfully!'
            ]);
        });
    }

    private function updatePaymentRequestData($paymentRequestData, $request, $ticket, $isTicket = false)
    {
        $updateData = [];

        if ($request->has('total_amount')) {
            $updateData['total_amount'] = $request->total_amount;
        }

        if ($request->has('supplier_name')) {
            $updateData['supplier_name'] = $request->supplier_name;
        }

        if ($request->has('bag_weight')) {
            $updateData['bag_weight'] = $request->bag_weight;
        }

        if ($request->has('bag_rate')) {
            $updateData['bag_rate'] = $request->bag_rate;
        }

        if ($request->has('bag_weight_amount')) {
            $updateData['bag_weight_amount'] = $request->bag_weight_amount;
        }

        if ($request->has('loading_weighbridge_amount')) {
            $updateData['loading_weighbridge_amount'] = $request->loading_weighbridge_amount;
        }

        if ($request->has('bag_rate_amount')) {
            $updateData['bag_rate_amount'] = $request->bag_rate_amount;
        }

        if (!empty($updateData)) {
            $paymentRequestData->update($updateData);
            if ($isTicket) {
                $ticket->update($updateData);
            }
        }
    }

    private function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();
        if ($request->has('sampling_results')) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['slab_type_id'] ?? null,
                    'name' => $result['slab_name'] ?? '',
                    'checklist_value' => $result['checklist_value'] ?? 0,
                    'suggested_deduction' => $result['suggested_deduction'] ?? 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => $result['deduction_type'] ?? 'amount',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                    'is_other_deduction' => false
                ]);
            }
        }

        if ($request->has('compulsory_results')) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'],
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => 'amount',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,

                ]);
            }
        }
    }

    public function edit($paymentRequestId)
    {
        $paymentRequest = PaymentRequest::with([
            // 'paymentRequestData',
            'paymentRequestData.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
        ])->findOrFail($paymentRequestId);

        $isUpdated = 0;
        $approval = null;

        if (!$paymentRequest->canBeApproved()) {
            $approval = PaymentRequestApproval::where('payment_request_id', $paymentRequestId)
                ->latest()
                ->first();
            $isUpdated = 1;
        }

        $moduleType = $paymentRequest->paymentRequestData->module_type;
        $ticket = null;
        if ($moduleType == 'ticket' || $moduleType == 'freight_payment') {
            $ticket = $paymentRequest->paymentRequestData->arrivalTicket;
        } else {
            $ticket = $paymentRequest->paymentRequestData->purchaseTicket;
        }

        $purchaseOrder = $ticket->purchaseOrder;

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
            $query->where('ticket_id', $ticket->id);
        })
            ->where('request_type', 'freight_payment')
            ->where('module_type', $moduleType)
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;
        // $paymentRequestData = PaymentRequestData::where('ticket_id', $ticket->id)->where('module_type', 'purchase_order')->orderByDesc('id')->first();
        $paymentRequestData = $paymentRequest->paymentRequestData;
        $brokers = Broker::all();

        if ($moduleType == 'ticket') {
            if ($ticket) {
                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();

                if ($samplingRequest) {
                    $rmPoSlabs = collect();

                    $samplingRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
                    $samplingRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

                    $productSlabCalculations = null;
                    if ($samplingRequest->arrival_product_id) {
                        $productSlabCalculations = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)->get();
                    }

                    foreach ($samplingRequestResults as &$result) {
                        $matchingSlabs = [];
                        $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);

                        if ($productSlabCalculations) {
                            $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                                ->values()
                                ->all();
                            if (!empty($matchingSlabs)) {
                                $result->deduction_type = $matchingSlabs[0]->deduction_type;
                            }
                        }
                        $result->matching_slabs = $matchingSlabs;
                    }
                }

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'brokers' => $brokers,
                'purchaseOrder' => $purchaseOrder,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'paymentRequestData' => $paymentRequestData,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        } else if ($moduleType == 'purchase_order') {
            if ($ticket) {
                $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();

                if ($samplingRequest) {
                    $rmPoSlabs = collect();
                    if ($purchaseOrder && $samplingRequest->arrival_product_id) {
                        $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $purchaseOrder->id)
                            ->where('product_id', $samplingRequest->arrival_product_id)
                            ->get()
                            ->groupBy('product_slab_type_id');
                    }

                    $samplingRequestCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)->get();
                    $samplingRequestResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)->get();

                    $productSlabCalculations = null;
                    if ($samplingRequest->arrival_product_id) {
                        $productSlabCalculations = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)->get();
                    }

                    foreach ($samplingRequestResults as &$result) {
                        $matchingSlabs = [];
                        $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);

                        if ($productSlabCalculations) {
                            $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                                ->values()
                                ->all();
                            if (!empty($matchingSlabs)) {
                                $result->deduction_type = $matchingSlabs[0]->deduction_type;
                            }
                        }
                        $result->matching_slabs = $matchingSlabs;
                    }
                }

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
                'ticket' => $ticket,
                'purchaseOrder' => $purchaseOrder,
                'brokers' => $brokers,
                'paymentRequestData' => $paymentRequestData,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        } else if ($moduleType == 'freight_payment') {
            $vendors = Vendor::all();
            $freightPaymentRequest = FreightPaymentRequest::where('arrival_ticket_id', $ticket->id)
                ->where('arrival_slip_id', $ticket->arrivalSlip->id)
                ->first();

            return view('management.procurement.raw_material.freight_request.create', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'freightPaymentRequest' => $freightPaymentRequest,
                'purchaseOrder' => $purchaseOrder,
                'vendors' => $vendors,
                'brokers' => $brokers,
                'isUpdated' => $isUpdated,
                'approval' => $approval,
                'paymentRequestData' => $paymentRequestData,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ]);

            return view('management.procurement.raw_material.freight_request.snippets.approvalForm', [
                'paymentRequest' => $paymentRequest,
                'ticket' => $ticket,
                'requestPurchaseForm' => $requestPurchaseForm,
                'approval' => $approval
            ]);
        }

        return view('management.procurement.raw_material.payment_request_approval.snippets.approvalForm', [
            'paymentRequest' => $paymentRequest,
            'ticket' => $ticket,
            'isUpdated' => $isUpdated,
            'requestPurchaseForm' => $requestPurchaseForm,
            'approval' => $approval
        ]);
    }
}
