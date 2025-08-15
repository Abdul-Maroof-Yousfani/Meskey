<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PaymentRequestRequest;
use App\Http\Requests\Procurement\TicketPaymentRequestRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\Broker;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\ProductSlabType;
use App\Models\Master\Supplier;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use App\Models\TruckSizeRange;
use Illuminate\Support\Facades\DB;

class TicketPaymentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.ticket_payment_request.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $query = ArrivalTicket::with([
            'purchaseOrder',
            'paymentRequestData.paymentRequests',
            'paymentRequestData.paymentRequests.approvals',
            'broker',
            'product',
            'qcProduct',
            'freight',
            'paymentRequestData' => function ($query) {
                $query->with(['paymentRequests' => function ($q) {
                    $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                        ->groupBy('payment_request_data_id', 'request_type', 'status');
                }]);
            }
        ])
            ->where('is_ticket_verified', 1)
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->where('first_qc_status', '!=', 'rejected')
            ->whereHas('purchaseOrder')->where('sauda_type_id', 1)->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('arrival_freights')
                    ->whereColumn('arrival_freights.arrival_ticket_id', 'arrival_tickets.id')
                    ->limit(1);
            })
            ->orderByDesc('created_at');

        if ($request->has('broker_id') && $request->broker_id != '') {
            $query->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('broker_id', $request->broker_id);
            });
        }

        if ($request->has('product_id') && $request->product_id != '') {
            $query->where('qc_product', $request->product_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unique_no', 'like', "%{$search}%")
                    ->orWhere('truck_no', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', function ($q) use ($search) {
                        $q->where('contract_no', 'like', "%{$search}%")
                            ->orWhere('ref_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder.supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->paginate($request->per_page ?? 10);

        $tickets->getCollection()->transform(function ($ticket) {
            $approvedPaymentSum = 0;
            $approvedFreightSum = 0;
            $totalPaymentSum = 0;
            $totalFreightSum = 0;
            $totalAmount = 0;
            $paidAmount = 0;
            $remainingAmount = 0;

            foreach ($ticket->paymentRequestData as $data) {
                $totalAmount = $data->total_amount ?? 0;
                $paidAmount = $data->paid_amount ?? 0;

                foreach ($data->paymentRequests as $pRequest) {
                    if ($pRequest->request_type == 'payment') {
                        $totalPaymentSum += $pRequest->total_amount;
                        if ($pRequest->status == 'approved') {
                            $approvedPaymentSum += $pRequest->total_amount;
                        }
                    } else {
                        $totalFreightSum += $pRequest->total_amount;
                        if ($pRequest->status == 'approved') {
                            $approvedFreightSum += $pRequest->total_amount;
                        }
                    }
                }
            }

            $remainingAmount = ($totalAmount - $approvedPaymentSum);

            $ticket->calculated_values = [
                'total_payment_sum' => $totalPaymentSum,
                'total_freight_sum' => $totalFreightSum,
                'approved_payment_sum' => $approvedPaymentSum,
                'approved_freight_sum' => $approvedFreightSum,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'created_at' => $ticket?->freight?->first()->created_at ?? $ticket->created_at
            ];

            return $ticket;
        });

        return view('management.procurement.raw_material.ticket_payment_request.getList', [
            'tickets' => $tickets,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::where('sauda_type_id', 2)->get();
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.ticket_payment_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketPaymentRequestRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';
            $requestData['module_type'] = 'ticket';

            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();
            $ticket = ArrivalTicket::where('id', $requestData['ticket_id'])->first();
            $purchaseOrder = ArrivalPurchaseOrder::where('id', $requestData['purchase_order_id'])->first();

            $existingApprovals = PaymentRequestData::where('purchase_order_id', $purchaseOrder->id)
                ->where('ticket_id', $ticket->id)
                ->count();

            $paymentRequestData = PaymentRequestData::create($requestData);

            $this->createPaymentRequests($paymentRequestData, $request);

            if (isset($request->sampling_results) || isset($request->compulsory_results)) {
                $this->saveSamplingResults($paymentRequestData, $request);
            }

            $this->manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals);

            return response()->json(['success' => 'Payment request created successfully']);
        });
    }

    public function manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals)
    {
        $paymentDetails = calculatePaymentDetails($requestData['ticket_id'], 1);
        $contractNo = $purchaseOrder->contract_no;
        $arrivalSlipNo = $ticket->arrivalSlip->unique_no;
        $truckNo = $ticket->truck_no ?? 'N/A';
        $biltyNo = $ticket->bilty_no ?? 'N/A';
        $grnNo = $ticket->arrivalSlip->unique_no;
        $qcAccountId = $ticket->qcProduct->account_id;
        $amount = $paymentDetails['calculations']['net_amount'] ?? 0;
        $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;

        $supplierTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'supplier-payable')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $supplierData = [
            'amount' =>   $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
            'account_id' => $purchaseOrder->supplier->account_id,
            'type' => 'credit',
            'counter_account_id' => $qcAccountId,
            'grn_no' => $grnNo,
            'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
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
                    'purpose' => "supplier-payable",
                    'counter_account_id' => $qcAccountId,
                    'grn_no' => $grnNo,
                    'payment_against' => "pohanch-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $supplierData['remarks']
                ]
            );
        }

        $transitTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'arrival-slip')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $transitData = [
            'amount' => $inventoryAmount,
            'account_id' => $ticket->qcProduct->account_id,
            'type' => 'debit',
            'counter_account_id' => $purchaseOrder->supplier->account_id,
            'grn_no' => $grnNo,
            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $ticket->arrived_net_weight . ' kg) at rate ' . $ticket->purchaseOrder->rate_per_kg . '/kg.'
        ];

        if ($transitTxn) {
            $transitTxn->update($transitData);
        } else {
            createTransaction(
                $amount,
                $ticket->qcProduct->account_id,
                1,
                $contractNo,
                'debit',
                'no',
                [
                    'purpose' => "arrival-slip",
                    'payment_against' => "pohanch-purchase",
                    'grn_no' => $grnNo,
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $transitData['remarks']
                ]
            );
        }

        $loadingWeight = $ticket->arrived_net_weight;

        if ($purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against',   'pohanch-purchase')
                ->where('account_id', $purchaseOrder->broker->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'account_id' => $purchaseOrder->broker->account_id,
                    'counter_account_id' => $qcAccountId,
                    'type' => 'credit',
                    'grn_no' => $grnNo,
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
                        'purpose' => "broker",
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against',   'pohanch-purchase')
                ->where('account_id', $purchaseOrder->brokerTwo->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'account_id' => $purchaseOrder->brokerTwo->account_id,
                    'counter_account_id' => $qcAccountId,
                    'type' => 'credit',
                    'grn_no' => $grnNo,
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
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against',   'pohanch-purchase')
                ->where('account_id', $purchaseOrder->brokerThree->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'counter_account_id' => $qcAccountId,
                    'amount' => $amount,
                    'grn_no' => $grnNo,
                    'account_id' => $purchaseOrder->brokerThree->account_id,
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
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if (
            isset($requestData['brokery_amount'], $requestData['broker_id']) &&
            $requestData['brokery_amount'] < 0
        ) {
            $existingTxn = Transaction::where('voucher_no', $contractNo)
                ->where('purpose', 'supplier-brokery')
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->exists();

            if (!$existingTxn) {
                $broker = Broker::find($requestData['broker_id']);
                if ($broker && $broker->account_id) {
                    $brokeryAmount = abs($requestData['brokery_amount']);

                    createTransaction(
                        $brokeryAmount,
                        $purchaseOrder->supplier->account_id,
                        1,
                        $contractNo,
                        'debit',
                        'no',
                        [
                            'purpose' => "supplier-brokery",
                            'grn_no' => $grnNo,
                            'counter_account_id' => $qcAccountId,
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
                            'purpose' => "supplier-brokery",
                            'counter_account_id' => $purchaseOrder->supplier->account_id,
                            'payment_against' => "thadda-purchase",
                            'grn_no' => $grnNo,
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => "Brokery amount adjustment received from supplier for contract ($contractNo)."
                        ]
                    );
                }
            }
        }
    }

    private function saveSamplingResults($paymentRequest, $request)
    {
        if ($request->sampling_results) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequest->id,
                    'slab_type_id' => $result['slab_type_id'],
                    'name' => $result['slab_name'],
                    'checklist_value' => $result['checklist_value'],
                    'suggested_deduction' => $result['suggested_deduction'],
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => $result['suggested_deduction'] > 0 ? 'amount' : 'percentage',
                    'deduction_amount' => $result['deduction_amount'],
                ]);
            }
        }

        // Save compulsory sampling results
        if ($request->compulsory_results) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequest->id,
                    'slab_type_id' => $result['qc_param_id'],
                    'name' => $result['qc_name'],
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => 'amount',
                    'deduction_amount' => $result['deduction_amount'],
                ]);
            }
        }

        if ($request->other_deduction && isset($request->other_deduction['kg_value']) && $request->other_deduction['kg_value'] > 0) {
            // PaymentRequestSamplingResult::create([
            //     'payment_request_data_id' => $paymentRequest->id,
            //     'slab_type_id' => null,
            //     'name' => $request->other_deduction['slab_name'] ?? 'Other Deduction',
            //     'checklist_value' => 0,
            //     'suggested_deduction' => 0,
            //     'applied_deduction' => $request->other_deduction['kg_value'],
            //     'deduction_type' => 'kg',
            //     'deduction_amount' => $request->other_deduction['deduction_amount'],
            //     'is_other_deduction' => true,
            //     'kg_value' => $request->other_deduction['kg_value']
            // ]);
        }
    }

    public function update(PaymentRequestRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $paymentRequestData = PaymentRequestData::findOrFail($id);

            // Prepare update data
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            // Calculate remaining amount
            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            // Update main data
            $paymentRequestData->update($requestData);

            // Delete existing payment requests and create new ones
            $paymentRequestData->paymentRequests()->delete();
            $this->createPaymentRequests($paymentRequestData, $request);

            // Update sampling results if exists
            if (isset($request->sampling_results) || isset($request->compulsory_results) || isset($request->other_deduction)) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request)
    {
        // dd([
        //     'payment_request_data_id' => $paymentRequestData->id,
        //     'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
        //     'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
        //     'request_type' => 'payment',
        //     'amount' => $request->payment_request_amount ?? 0
        // ]);

        if (isset($request->ticket_id)) {
            $ticket = ArrivalTicket::find($request->ticket_id);
            // dd($request->bag_weight);
            if ($ticket && $ticket->purchaseOrder) {
                // $ticket->purchaseOrder->update(['bag_weight' => $request->bag_weight]);
                $ticket->update(['bag_weight' => $request->bag_weight]);
            }
        }

        PaymentRequest::create([
            'payment_request_data_id' => $paymentRequestData->id,
            'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
            'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
            'request_type' => 'payment',
            'module_type' => 'ticket',
            'amount' => $request->payment_request_amount ?? 0
        ]);

        // if ($request->freight_pay_request_amount && $request->freight_pay_request_amount > 0) {
        //     PaymentRequest::create([
        //         'payment_request_data_id' => $paymentRequestData->id,
        //         'request_type' => 'freight_payment',
        // 'module_type' => 'ticket',
        //         'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
        //         'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
        //         'amount' => $request->freight_pay_request_amount
        //     ]);
        // }
    }

    protected function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();

        if (isset($request->sampling_results)) {
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

        if (isset($request->compulsory_results)) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'qc_param_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'] ?? '',
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['arrivalTicket'] = $arrivalTicket = ArrivalTicket::findOrFail($id);
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        $paymentRequestData = PaymentRequestData::where('ticket_id', $arrivalTicket->id)->where('module_type', 'ticket')->orderByDesc('id')->first();

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket, $id) {
            $q->where('ticket_id', $id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'payment')
            ->sum('amount');
        // dd($requestedAmount, $arrivalTicket->arrival_purchase_order_id, $id);
        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'payment')
            ->where('status', 'approved')
            ->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($arrivalTicket) {
            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();

            if ($samplingRequest) {
                $rmPoSlabs = collect();
                if ($arrivalTicket && $samplingRequest->arrival_product_id) {
                    // $rmPoSlabs = ProductSlabForRmPo::where('arrival_ticket_id', $arrivalTicket->id)
                    //     ->where('product_id', $samplingRequest->arrival_product_id)
                    //     ->get()
                    //     ->groupBy('product_slab_type_id');
                }

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

            $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($arrivalTicket) {
                $query->where('ticket_id', $arrivalTicket->id);
                $query->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
            })->select('other_deduction_kg', 'other_deduction_value')
                ->latest()
                ->first();
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
        }

        $brokers = Broker::all();
        $data['purchaseOrder'] = $arrivalTicket->purchaseOrder;

        $data['html'] = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
            'purchaseOrder' => $arrivalTicket->purchaseOrder,
            'brokers' => $brokers,
            'arrivalTicket' => $arrivalTicket,
            'paymentRequestData' => $paymentRequestData,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'isRequestApprovalPage' => false,
            'isTicketApprovalPage' => false,
            'isTicketPage' => true,
        ])->render();

        return view('management.procurement.raw_material.ticket_payment_request.create', $data);
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

    public function getSlabsByPaymentRequestParams(Request $request)
    {
        $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->purchase_order_id);

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('purchase_order_id', $purchaseOrder->id))
            ->where('request_type', 'payment')->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('purchase_order_id', $purchaseOrder->id))
            ->where('request_type', 'payment')->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->get();

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($purchaseOrder) {
            $samplingRequest = PurchaseSamplingRequest::where('arrival_purchase_order_id', $request->purchase_order_id)
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

            $existingPaymentRequestData = PaymentRequestData::where('purchase_order_id', $request->purchase_order_id)->first();
            if ($existingPaymentRequestData) {
                // $otherDeduction = PaymentRequestSamplingResult::where('payment_request_data_id', $existingPaymentRequestData->id)
                //     ->where('is_other_deduction', true)
                //     ->first();
            }
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
            // dd($samplingRequestResults, $orignalsamplingRequestResults, $rmPoSlabs);
        }

        $html = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
